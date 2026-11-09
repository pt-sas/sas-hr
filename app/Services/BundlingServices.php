<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\M_Bundling;
use App\Models\M_BundlingParticipant;
use App\Models\M_DocumentType;
use App\Models\M_Employee;
use App\Models\M_MasterBundling;
use App\Models\M_Rule;
use App\Models\M_RuleDetail;
use App\Services\BaseServices;

class BundlingServices extends BaseServices
{
    protected $baseSubType;

    public function __construct(int $userID, int $employeeID, int $baseSubType)
    {
        parent::__construct();

        $this->userID = $userID;
        $this->employeeID = $employeeID;

        $this->model = new M_Bundling($this->request);
        $this->modelDetail = new M_BundlingParticipant($this->request);
        $this->entity = new \App\Entities\Bundling();

        $this->baseSubType = $baseSubType;
    }

    public function create(array $data)
    {
        $this->model->db->transBegin();

        try {
            $mRule = new M_Rule($this->request);
            // $mRuleDetail = new M_RuleDetail($this->request);

            $_ID       = !empty($data[$this->model->primaryKey]) ? $data[$this->model->primaryKey] : null;

            $today = date('Y-m-d');
            $startDate = date('Y-m-d', strtotime($data['startdate']));

            $data['submissiontype'] = $this->baseSubType;

            if ($this->baseSubType === 100033) {
                $data['necessary']      = 'PR';
            } else if ($this->baseSubType === 100031) {
                $data['necessary']      = 'PP';
            } else if ($this->baseSubType === 100034) {
                $data['necessary']      = 'PI';
            } else {
                throw new ValidationException("Tipe pengajuan tidak valid");
            }

            $doProcess = $data['processNow'] ?? "N";

            $rule = $mRule->where('name', 'Pengajuan Paket')->first();
            if (!$rule) throw new NotFoundException("Rule Pengajuan Paket belum dibuat");

            // $ruleDetail = $mRuleDetail->where([$mRule->primarykey => $rule->{$mRule->primarykey}, 'name' => 'Batas Waktu Pengajuan'])->first();

            // $maxHour = !empty($ruleDetail->condition) ? $ruleDetail->condition : '23:59';

            //* Validate max hour
            // if ($startDate >= $today && date('H:i') > $maxHour)
            //     throw new ValidationException("Maksimal membuat atau mengedit pengajuan adalah jam {$maxHour}");

            //* Validate day
            if ($startDate < $today)
                throw new ValidationException("Tidak bisa buat pengajuan, karena sudah melewati batas hari");

            //* Validate Employee Participant
            $checkEmployee = [];
            foreach (json_decode($data['table']) as $row) {
                $employeeId = $row->md_employee_id;

                if (isset($checkEmployee[$employeeId])) {
                    $mEmployee = new M_Employee($this->request);
                    $employee = $mEmployee->find($employeeId);
                    throw new ValidationException(
                        "Karyawan {$employee->value} terpilih lebih dari 1 pada pengajuan ini, mohon check kembali peserta paket"
                    );
                }

                $checkEmployee[$employeeId] = true;
            }

            $this->entity->fill($data);
            $this->table = $data['table'];

            if (!$_ID) {
                $this->entity->setDocStatus($this->DOCSTATUS_Drafted);
                $docNo = $this->model->getInvNumber("submissiontype", $this->baseSubType, $data, $this->userID);
                $this->entity->setDocumentNo($docNo);
            } else {
                $this->entity->setBundlingId($_ID);
            }

            $result = $this->save(false);

            if ($doProcess === "Y") {
                if (!$_ID) $_ID = $this->insertID;
                $this->proccessTransaction($_ID, $this->DOCSTATUS_Completed);
            }

            $this->model->db->transCommit();

            return $result;
        } catch (\Throwable $e) {
            $this->model->db->transRollback();
            throw $e;
        }
    }

    public function show(int $id)
    {
        $mEmployee = new M_Employee($this->request);
        $mMasterPacket = new M_MasterBundling($this->request);

        $list = $this->model->where([$this->model->primaryKey => $id, 'submissiontype' => $this->baseSubType])
            ->findAll();

        if (empty($list))
            throw new NotFoundException("Pengajuan tidak ditemukan");

        $rowEmp = $mEmployee->where($mEmployee->primaryKey, $list[0]->getEmployeeId())->first();
        $list = $this->field->setDataSelect($mEmployee->table, $list, $mEmployee->primaryKey, $rowEmp->getEmployeeId(), $rowEmp->getValue());

        $rowPacket = $mMasterPacket->where($mMasterPacket->primaryKey, $list[0]->trx_master_bundling_id)->first();
        $list = $this->field->setDataSelect($mMasterPacket->table, $list, $mMasterPacket->primaryKey, $rowPacket->trx_master_bundling_id, $rowPacket->name);

        $detail = $this->modelDetail->where($this->model->primaryKey, $id)->findAll();

        return [
            'title' => $list[0]->getDocumentNo() . '_' . $rowEmp->getFullName(),
            'header' => $list,
            'detail' => $detail
        ];
    }

    public function proccessTransaction(int $id, String $docaction, int $subTypeTarget = null)
    {
        //* Call Services
        $WScenarioServices = new WScenarioServices($this->userID, $this->employeeID);

        //* Call Models
        $mDocType = new M_DocumentType($this->request);

        //* Get Data Transaction
        $row = $this->model->where([$this->model->primaryKey => $id, 'submissiontype' => $this->baseSubType])->first();

        if (empty($row))
            throw new NotFoundException("Pengajuan tidak ditemukan");

        if ($docaction === $row->getDocStatus())
            throw new ValidationException("Silahkan refresh terlebih dahulu");

        $today = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime($row->startdate));

        //* Get menu URL
        $docType = $mDocType->getDocTypeMenu($row->submissiontype);

        if (empty($docType->sys_submenu_id))
            throw new NotFoundException("Tipe Pengajuan {$docType->name} belum diset acuan menu-nya");

        //* Checking docaction condition
        if ($docaction === $this->DOCSTATUS_Completed) {
            if ($startDate < $today)
                throw new ValidationException("Pengajuan ini sudah tidak bisa diproses complete, karena sudah melewati batas hari");

            $WScenarioServices->setScenario($this->entity, $this->model, $this->modelDetail, $id, $docaction, $docType->url);

            return 'Pengajuan berhasil Diproses';
        } else if ($docaction === $this->DOCSTATUS_Voided) {
            $this->entity->setDocStatus($this->DOCSTATUS_Voided);
            $this->entity->setMasterBundlingId($id);
            $this->save();
            return 'Pengajuan berhasil Divoid';
        } else {
            throw new BusinessException("Dokumen aksi ini tidak tersedia pada tipe pengajuan ini");
        }
    }
}

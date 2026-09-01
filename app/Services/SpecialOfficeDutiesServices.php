<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Models\M_Assignment;
use App\Models\M_AssignmentDate;
use App\Models\M_AssignmentDetail;
use App\Models\M_DocumentType;
use App\Services\PeriodServices;

class SpecialOfficeDutiesServices extends BaseServices
{
    protected $baseSubType;

    public function __construct(int $userID, int $employeeID)
    {
        parent::__construct();

        $this->userID = $userID;
        $this->employeeID = $employeeID;

        $this->model = new M_Assignment($this->request);
        $this->modelDetail = new M_AssignmentDetail($this->request);
        $this->modelSubDetail = new M_AssignmentDate($this->request);
        $this->entity = new \App\Entities\Assignment();
        $this->baseSubType = $this->model->Pengajuan_Penugasan;
    }

    public function proccessTransaction(int $id, string $docaction, int $subTypeTarget = null)
    {
        $WScenarioServices = new WScenarioServices($this->userID, $this->employeeID);
        $periodServices    = new PeriodServices($this->userID, $this->employeeID);

        $mDocType    = new M_DocumentType($this->request);

        $row = $this->model->where([$this->model->primaryKey => $id, 'submissiontype' => $this->baseSubType])->first();

        if (empty($row))
            throw new NotFoundException("Pengajuan tidak ditemukan");

        if ($docaction === $row->getDocStatus())
            throw new ValidationException("Silahkan refresh terlebih dahulu");

        $docType = $mDocType->getDocTypeMenu($row->submissiontype);

        if (empty($docType->sys_submenu_id))
            throw new NotFoundException("Tipe Pengajuan {$docType->name} belum diset acuan menu-nya");

        $startDate = date('Y-m-d', strtotime($row->startdate));
        $endDate   = date('Y-m-d', strtotime($row->enddate));

        $periodServices->validatePeriod($row->submissiontype, $startDate, $endDate);

        if ($docaction === $this->DOCSTATUS_Completed) {
            //* Create detail lines if not yet present
            $line = $this->modelDetail->where($this->model->primaryKey, $id)->findAll();
            $assignmentDate = $this->modelSubDetail->where("trx_assignment_detail_id", $line[0]->trx_assignment_detail_id)->first();

            if (empty($assignmentDate)) {
                foreach ($line as $row) {
                    $data = [
                        'id'         => $row->trx_assignment_detail_id,
                        'created_by' => $this->userID,
                        'updated_by' => $this->userID
                    ];
                    $this->model->createAssignmentDate($data, $row);
                }
            }

            $WScenarioServices->setScenario($this->entity, $this->model, $this->modelDetail, $id, $docaction, $docType->url, $this->modelSubDetail, true);

            return 'Pengajuan berhasil Diproses';
        } else if ($docaction === $this->DOCSTATUS_Voided) {
            $this->entity->setDocStatus($this->DOCSTATUS_Voided);
            $this->entity->setAbsentId($id);
            $this->save();
            return 'Pengajuan berhasil Divoid';
        } else {
            throw new BusinessException("Dokumen aksi ini tidak tersedia pada tipe pengajuan ini");
        }
    }
}

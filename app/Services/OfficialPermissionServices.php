<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Models\M_Absent;
use App\Models\M_AbsentDetail;
use App\Models\M_DocumentType;
use App\Models\M_Holiday;
use App\Models\M_Configuration;
use App\Models\M_WorkDetail;
use App\Services\EmpWorkDayServices;
use App\Services\PeriodServices;

class OfficialPermissionServices extends BaseServices
{
    protected $baseSubType;

    public function __construct(int $userID, int $employeeID)
    {
        parent::__construct();

        $this->userID = $userID;
        $this->employeeID = $employeeID;

        $this->model = new M_Absent($this->request);
        $this->modelDetail = new M_AbsentDetail($this->request);
        $this->entity = new \App\Entities\Absent();
        $this->baseSubType = $this->model->Pengajuan_Ijin_Resmi;
    }

    public function proccessTransaction(int $id, string $docaction, int $subTypeTarget = null)
    {
        $WScenarioServices = new WScenarioServices($this->userID, $this->employeeID);
        $periodServices    = new PeriodServices($this->userID, $this->employeeID);
        $eWorkDayServices  = new EmpWorkDayServices($this->userID, $this->employeeID);

        $mDocType    = new M_DocumentType($this->request);
        $mHoliday    = new M_Holiday($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);
        $mConfig = new M_Configuration($this->request);

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
        $holidays  = $mHoliday->getHolidayDate();

        $workDay = $eWorkDayServices->getEmpWorkDay($row->md_employee_id, $startDate, $endDate);

        $whereClause  = "md_work_detail.isactive = 'Y'";
        $whereClause .= " AND md_employee_work.md_employee_id = $row->md_employee_id";
        $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
        $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getResult();

        $daysOff = getDaysOff($workDetail);

        $periodServices->validatePeriod($row->submissiontype, $startDate, $endDate, $holidays, $daysOff);

        if ($docaction === $this->DOCSTATUS_Completed) {
            //* Validate duplicate submission
            $this->validateDuplicateSubmission($row->md_employee_id, $startDate, $endDate);

            //* Create detail lines if not yet present
            $line = $this->modelDetail->where($this->model->primaryKey, $id)->first();

            if (empty($line)) {
                $data = [
                    'id'         => $id,
                    'created_by' => $this->userID,
                    'updated_by' => $this->userID
                ];

                $this->model->createAbsentDetail($data, $row);
            }

            $WScenarioServices->setScenario($this->entity, $this->model, $this->modelDetail, $id, $docaction, $docType->url, null, true);

            return 'Pengajuan berhasil Diproses';
        } else if ($docaction === $this->DOCSTATUS_Voided) {
            $this->entity->setDocStatus($this->DOCSTATUS_Voided);
            $this->entity->setAbsentId($id);
            $this->save();
            return 'Pengajuan berhasil Divoid';
        } else if ($docaction === $this->DOCSTATUS_Reopen) {
            $config = $mConfig->where('name', "MAX_DATE_REOPEN")->first();

            $rule = $mRule->where([
                'name'      => 'Ijin Resmi',
                'isactive'  => 'Y'
            ])->first();

            $ruleDetail = $mRuleDetail->where(['md_rule_id' => $rule->md_rule_id, 'name' => 'Batas Reopen'])->first();

            $maxDateReopen = DateTime::createFromFormat('d-m', $config->value);
            $dateRange = getDatesFromRange($row->submissiondate, $today, $holidays, 'Y-m-d');

            //* Validate Reopen
            if (empty($subTypeTarget))
                throw new ValidationException("Silahkan pilih tipe form dahulu.");

            if ($row->md_employee_id == $this->employeeID)
                throw new ValidationException("Tidak bisa reopen untuk pengajuan diri sendiri");

            if ($startDate > date('Y-m-d', strtotime($row->submissiondate)))
                throw new ValidationException("Tidak bisa reopen untuk pengajuan future");

            if ($today > $maxDateReopen->format('Y-m-d'))
                throw new ValidationException("Batas reopen tanggal 24 Desember");

            if (count($dateRange) > ($ruleDetail ? $ruleDetail->condition : 1))
                throw new ValidationException("Sudah melewati batas waktu reopen");

            if ($row->isreopen == "Y")
                throw new ValidationException("Dokumen ini sudah tidak bisa direopen");

            if ($subTypeTarget != $this->baseSubType)
                throw new BusinessException("Tipe pengajuan ini tidak bisa direopen ke tipe pengajuan lain");

            //* Do Save
            $this->entity->setDocStatus($this->DOCSTATUS_Drafted);
            $this->entity->setIsReopen('Y');
            $this->entity->setIsApproved('');

            $this->save();

            return "Dokumen berhasil direopen";
        } else {
            throw new BusinessException("Dokumen aksi ini tidak tersedia pada tipe pengajuan ini");
        }
    }

    private function validateDuplicateSubmission(int $md_employee_id, $startDate, $endDate)
    {
        $whereClause  = "v_all_submission.md_employee_id = {$md_employee_id}";
        $whereClause .= " AND DATE_FORMAT(v_all_submission.date, '%Y-%m-%d') BETWEEN '{$startDate}' AND '{$endDate}'";
        $whereClause .= " AND v_all_submission.docstatus IN ('{$this->DOCSTATUS_Inprogress}','{$this->DOCSTATUS_Completed}')";
        $whereClause .= " AND v_all_submission.submissiontype IN (" . implode(", ", $this->Form_Satu_Hari) . ")";
        $whereClause .= " AND v_all_submission.isagree IN ('{$this->LINESTATUS_Disetujui}', '{$this->LINESTATUS_Realisasi_HRD}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Approval}')";
        $trx = $this->model->getAllSubmission($whereClause)->getRow();

        if ($trx)
            throw new BusinessException("Tidak bisa mengajukan pada rentang tanggal, karena sudah ada pengajuan lain");
    }
}

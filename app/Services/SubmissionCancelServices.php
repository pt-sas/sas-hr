<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Models\M_Absent;
use App\Models\M_Attendance;
use App\Models\M_DocumentType;
use App\Models\M_SubmissionCancel;
use App\Models\M_SubmissionCancelDetail;
use App\Services\PeriodServices;

class SubmissionCancelServices extends BaseServices
{
    protected $baseSubType;

    public function __construct(int $userID, int $employeeID)
    {
        parent::__construct();

        $this->userID = $userID;
        $this->employeeID = $employeeID;

        $this->model = new M_SubmissionCancel($this->request);
        $this->modelDetail = new M_SubmissionCancelDetail($this->request);
        $this->entity = new \App\Entities\SubmissionCancel();
        $this->baseSubType = $this->model->Pengajuan_Pembatalan;
    }

    public function proccessTransaction(int $id, string $docaction, int $subTypeTarget = null)
    {
        $WScenarioServices = new WScenarioServices($this->userID, $this->employeeID);
        $periodServices    = new PeriodServices($this->userID, $this->employeeID);

        $mDocType    = new M_DocumentType($this->request);
        $mAttendance = new M_Attendance($this->request);
        $mAbsent = new M_Absent($this->request);

        $today = date('Y-m-d');

        $row = $this->model->where([$this->model->primaryKey => $id, 'submissiontype' => $this->baseSubType])->first();

        if (empty($row))
            throw new NotFoundException("Pengajuan tidak ditemukan");

        $rowDetail = $this->modelDetail->where($this->model->primaryKey, $row->trx_submission_cancel_id)->findAll();

        if ($docaction === $row->getDocStatus())
            throw new ValidationException("Silahkan refresh terlebih dahulu");

        $docType = $mDocType->getDocTypeMenu($row->submissiontype);

        if (empty($docType->sys_submenu_id))
            throw new NotFoundException("Tipe Pengajuan {$docType->name} belum diset acuan menu-nya");

        foreach ($rowDetail as $val) {
            $periodServices->validatePeriod($row->submissiontype, $val->date, $val->date);
        }

        if ($docaction === $this->DOCSTATUS_Completed) {
            $keys = array_keys($rowDetail);
            $lastLoop = end($keys);

            $process = false;
            foreach ($rowDetail as $key => $value) {
                $dateClause = date('Y-m-d', strtotime($value->date));

                // TODO : Get Cancel Submission
                $whereClause = "trx_submission_cancel_detail.md_employee_id = '{$value->md_employee_id}'";
                $whereClause .= " AND trx_submission_cancel_detail.date = '{$dateClause}'";
                $whereClause .= " AND trx_submission_cancel_detail.isagree != 'N'";
                $whereClause .= " AND trx_submission_cancel.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')";
                $whereClause .= " AND trx_submission_cancel.ref_submissiontype = {$row->getRefSubmissionType()}";
                $whereClause .= " AND trx_submission_cancel.reference_id = {$row->getReferenceId()}";

                $trxSubmissionCancel = $this->modelDetail->getDetail(null, $whereClause)->getRow();

                if ($dateClause == $today) {
                    //TODO : Get attendance employee
                    $whereClause = "v_attendance.md_employee_id = '{$value->md_employee_id}'";
                    $whereClause .= " AND v_attendance.date = '{$dateClause}'";
                    $attPresent = $mAttendance->getAttendance($whereClause)->getRow();

                    //TODO : Get submission Office Duties
                    $whereClause = "v_all_submission.md_employee_id = {$value->md_employee_id}";
                    $whereClause .= " AND DATE_FORMAT(v_all_submission.date, '%Y-%m-%d') = '{$dateClause}'";
                    $whereClause .= " AND v_all_submission.submissiontype IN ($mAbsent->Pengajuan_Tugas_Kantor)";
                    $whereClause .= " AND v_all_submission.isagree IN ('{$this->LINESTATUS_Disetujui}', '{$this->LINESTATUS_Realisasi_HRD}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Approval}')";
                    $trxOfficeDuties = $mAbsent->getAllSubmission($whereClause)->getResult();
                }

                $dateNow = format_dmy($value->date, '-');

                if ($dateClause < $today) {
                    throw new ValidationException("Tidak bisa proses dokumen, tanggal {$dateNow} sudah melewati batas pembatalan");
                } else if (($dateClause == $today) && is_null($attPresent) && is_null($trxOfficeDuties)) {
                    throw new ValidationException("Tidak bisa proses dokumen, tanggal {$dateNow} sudah tidak ada kehadiran");
                } else if ($trxSubmissionCancel) {
                    throw new ValidationException("Tidak bisa proses dokumen, sudah ada pengajuan lain dengan nomor dokumen : {$trxSubmissionCancel->documentno}");
                }

                if ($key === $lastLoop)
                    $process = true;
            }

            if ($process) {
                $WScenarioServices->setScenario($this->entity, $this->model, $this->modelDetail, $id, $docaction, $docType->url, null, true);
            }

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

<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Models\M_Absent;
use App\Models\M_AbsentDetail;
use App\Models\M_Branch;
use App\Models\M_Division;
use App\Models\M_DocumentType;
use App\Models\M_Employee;
use App\Models\M_Holiday;
use App\Models\M_MedicalCertificate;
use App\Models\M_Rule;
use App\Models\M_Attendance;
use App\Models\M_WorkDetail;
use App\Services\EmpWorkDayServices;
use App\Services\PeriodServices;

class SickLeaveServices extends BaseServices
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
        $this->baseSubType = $this->model->Pengajuan_Sakit;
    }

    public function getPaginated(array $params, int $md_employee_id)
    {
        $page      = $params['page'];
        $limit     = $params['limit'];
        $docstatus = $params['docstatus'];
        $search    = $params['search'];

        $offset = ($page - 1) * $limit;

        $builder = $this->model->builder;

        $builder->select("trx_absent_id, documentno, startdate, enddate, docstatus, e.md_employee_id, e.value as karyawan");
        $builder->join('md_employee e', 'e.md_employee_id = trx_absent.md_employee_id', 'left');
        $builder->where('e.md_employee_id', $md_employee_id);
        $builder->where('submissiontype', $this->baseSubType);

        if (!empty($docstatus))
            $builder->where('docstatus', $docstatus);

        if (!empty($search)) {
            $searchFields = ['documentno', 'docstatus', 'startdate', 'enddate', 'e.value'];

            $builder->groupStart();
            foreach ($searchFields as $i => $field) {
                if ($i == 0)
                    $builder->like($field, $search);
                else
                    $builder->orLike($field, $search);
            }
            $builder->groupEnd();
        }

        $total = $builder->countAllResults(false);

        $builder->orderBy('documentno', 'DESC');

        $data = $builder->limit($limit, $offset)->get()->getResultArray();

        return [
            'data' => $data,
            'meta' => [
                'page'       => $page,
                'limit'      => $limit,
                'total'      => $total,
                'total_page' => ceil($total / $limit),
                'sort_by'    => 'documentno'
            ]
        ];
    }

    public function create(array $data)
    {
        $eWorkDayServices = new EmpWorkDayServices($this->userID, $this->employeeID);
        $periodServices   = new PeriodServices($this->userID, $this->employeeID);

        $mHoliday    = new M_Holiday($this->request);
        $mRule       = new M_Rule($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);
        $mAttendance = new M_Attendance($this->request);

        $_ID       = !empty($data[$this->model->primaryKey]) ? $data[$this->model->primaryKey] : null;
        $holidays  = $mHoliday->getHolidayDate();
        $startDate = date('Y-m-d', strtotime($data['startdate']));
        $endDate   = date('Y-m-d', strtotime($data['enddate']));
        $subDate   = date('Y-m-d', strtotime($data['submissiondate']));
        $employeeId = $data['md_employee_id'];

        if ($_ID) {
            $sql = $this->model->where([$this->model->primaryKey => $_ID, 'submissiontype' => $this->baseSubType])->first();

            if ($sql->docstatus != $this->DOCSTATUS_Drafted)
                throw new ValidationException("Tidak bisa edit, dokumen sudah diproses");
        }

        $data['submissiontype'] = $this->baseSubType;
        $data['necessary']      = 'SA';

        $rule = $mRule->where(['name' => 'Sakit', 'isactive' => 'Y'])->first();

        $minDays = $rule && !empty($rule->min) ? $rule->min : 1;
        $maxDays = $rule && !empty($rule->max) ? $rule->max : 1;

        $workDay = $eWorkDayServices->getEmpWorkDay($employeeId, $startDate, $endDate);

        $whereClause  = "md_work_detail.isactive = 'Y'";
        $whereClause .= " AND md_employee_work.md_employee_id = $employeeId";
        $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
        $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getResult();

        $daysOff      = getDaysOff($workDetail);
        $daysOffStr   = implode(', ', $daysOff);
        $dateWorkRange = getDatesFromRange($startDate, $endDate, $holidays, 'Y-m-d', 'all', $daysOff);

        $workClause = [];
        foreach ($dateWorkRange as $date) {
            $workClause[] = "'" . date('Y-m-d', strtotime($date)) . "'";
        }
        $workClause = implode(', ', $workClause);

        //* Check attendance on submission range
        $attWhereClause  = "v_attendance.md_employee_id = '{$employeeId}'";
        $attWhereClause .= " AND v_attendance.date IN ({$workClause})";
        $attPresent = $mAttendance->getAttendance($attWhereClause)->getResult();

        if ($attPresent) {
            $dates = implode(', ', array_map(fn($v) => format_dmy($v->date, '-'), $attPresent));
            throw new ValidationException("Ada kehadiran, tidak bisa mengajukan pada tanggal : [{$dates}]");
        }

        //* Past submission: attendance-based deadline check
        if ($startDate <= $subDate) {
            $attDate  = [];
            $lastDate = [];

            $dateRange = getDatesFromRange($startDate, $subDate, [], 'Y-m-d', 'all', []);

            foreach ($dateRange as $date) {
                $attCheckClause  = "v_attendance.md_employee_id = {$employeeId}";
                $attCheckClause .= " AND v_attendance.date = '{$date}'";
                $attCheckClause .= " AND DATE_FORMAT(v_attendance.date, '%w') NOT IN ({$daysOffStr})";
                $attPresentNextDay = $mAttendance->getAttendance($attCheckClause)->getRow();

                $trxCheckClause  = "trx_absent.md_employee_id = {$employeeId}";
                $trxCheckClause .= " AND DATE_FORMAT(trx_absent_detail.date, '%Y-%m-%d') = '{$date}'";
                $trxCheckClause .= " AND trx_absent.submissiontype IN ({$this->model->Pengajuan_Tugas_Kantor}, {$this->model->Pengajuan_Tugas_Kantor_setengah_Hari})";
                $trxCheckClause .= " AND trx_absent_detail.isagree IN ('Y','M','S')";
                $trxCheckClause .= " AND DATE_FORMAT(trx_absent_detail.date, '%w') NOT IN ({$daysOffStr})";
                $trxPresentNextDay = $this->modelDetail->getAbsentDetail($trxCheckClause)->getRow();

                if ($attPresentNextDay || $trxPresentNextDay)
                    $attDate[] = $date;

                $lastDate[] = $date;

                if (count($attDate) == $minDays)
                    break;
            }

            $lastDate = end($lastDate);

            if ($lastDate < $subDate)
                throw new ValidationException("Maksimal tanggal pengajuan pada tanggal : " . format_dmy($lastDate, '-'));
        }

        //* Validate duplicate submission
        $this->validateDuplicateSubmission($employeeId, $startDate, $endDate);

        //* Validate max future days
        $addDays = lastWorkingDays($subDate, [], $maxDays, false, [], true);
        $addDays = end($addDays);

        if ($endDate > $addDays)
            throw new ValidationException("Tanggal selesai melewati tanggal ketentuan");

        //* Validate Period
        $periodServices->validatePeriod($this->baseSubType, $startDate, $endDate, $holidays, $daysOff);

        //* Upload Images
        $uploadServices = new UploadServices($this->userID, $this->employeeID);
        $path = $this->PATH_UPLOAD . $this->PATH_Pengajuan . '/';

        $file  = $this->request->getFile('image');
        $file2 = $this->request->getFile('image2');
        $file3 = $this->request->getFile('image3');

        if ($_ID) {
            $sql = $sql ?? $this->model->where($this->model->primaryKey, $_ID)->first();

            if (empty($data['image']) && !empty($sql->getImage()) && file_exists($path . $sql->getImage()))
                unlink($path . $sql->getImage());

            if (empty($data['image2']) && !empty($sql->getImage2()) && file_exists($path . $sql->getImage2()))
                unlink($path . $sql->getImage2());

            if (empty($data['image3']) && !empty($sql->getImage3()) && file_exists($path . $sql->getImage3()))
                unlink($path . $sql->getImage3());
        }

        $data['image']  = $uploadServices->saveImage($file,  $employeeId, $this->baseSubType);
        $data['image2'] = $uploadServices->saveImage($file2, $employeeId, $this->baseSubType, '2');
        $data['image3'] = $uploadServices->saveImage($file3, $employeeId, $this->baseSubType, '3');

        $this->entity->fill($data);

        if (!$_ID) {
            $this->entity->setDocStatus($this->DOCSTATUS_Drafted);
            $docNo = $this->model->getInvNumber("submissiontype", $this->baseSubType, $data, $this->userID);
            $this->entity->setDocumentNo($docNo);
        } else {
            $this->entity->setAbsentId($_ID);
        }

        return $this->save();
    }

    public function show(int $id)
    {
        $mEmployee = new M_Employee($this->request);
        $mBranch   = new M_Branch($this->request);
        $mDivision = new M_Division($this->request);

        $fieldsAllowed = [
            'trx_absent_id', 'documentno', 'md_employee_id', 'nik',
            'md_branch_id', 'md_division_id', 'submissiondate', 'receiveddate',
            'submissiontype', 'startdate', 'enddate', 'reason', 'docstatus',
            'approveddate', 'created_by', 'updated_by', 'totaldays', 'isreopen',
            'image', 'image2', 'image3', 'img_medical'
        ];

        $list = $this->model->select($fieldsAllowed)
            ->where([$this->model->primaryKey => $id, 'submissiontype' => $this->baseSubType])
            ->findAll();

        if (empty($list))
            throw new NotFoundException("Pengajuan tidak ditemukan");

        $path = $this->PATH_UPLOAD . $this->PATH_Pengajuan . '/';
        $urlPath = 'uploads/' . $this->PATH_Pengajuan . '/';

        if (!empty($list[0]->getImage()) && file_exists($path . $list[0]->getImage())) {
            $list[0]->setImage($urlPath . $list[0]->getImage());
        } else {
            $list[0]->setImage(null);
        }

        if (!empty($list[0]->getImage2()) && file_exists($path . $list[0]->getImage2())) {
            $list[0]->setImage2($urlPath . $list[0]->getImage2());
        } else {
            $list[0]->setImage2(null);
        }

        if (!empty($list[0]->getImage3()) && file_exists($path . $list[0]->getImage3())) {
            $list[0]->setImage3($urlPath . $list[0]->getImage3());
        } else {
            $list[0]->setImage3(null);
        }

        $rowEmp = $mEmployee->where($mEmployee->primaryKey, $list[0]->getEmployeeId())->first();
        $list   = $this->field->setDataSelect($mEmployee->table, $list, $mEmployee->primaryKey, $rowEmp->getEmployeeId(), $rowEmp->getValue());

        $rowBranch = $mBranch->where($mBranch->primaryKey, $list[0]->getBranchId())->first();
        $list      = $this->field->setDataSelect($mBranch->table, $list, $mBranch->primaryKey, $rowBranch->getBranchId(), $rowBranch->getName());

        $rowDiv = $mDivision->where($mDivision->primaryKey, $list[0]->getDivisionId())->first();
        $list   = $this->field->setDataSelect($mDivision->table, $list, $mDivision->primaryKey, $rowDiv->getDivisionId(), $rowDiv->getName());

        $fieldsAllowed = [
            'trx_absent_detail_id', 'trx_absent_id', 'lineno',
            'date', 'isagree', 'ref_absent_detail_id', 'table'
        ];
        $detail = $this->modelDetail->select($fieldsAllowed)
            ->where($this->model->primaryKey, $id)
            ->findAll();

        return [
            'header' => $list,
            'line'   => $detail
        ];
    }

    public function proccessTransaction(int $id, string $docaction, int $subTypeTarget = null)
    {
        $WScenarioServices = new WScenarioServices($this->userID, $this->employeeID);
        $periodServices    = new PeriodServices($this->userID, $this->employeeID);
        $eWorkDayServices  = new EmpWorkDayServices($this->userID, $this->employeeID);

        $mDocType    = new M_DocumentType($this->request);
        $mHoliday    = new M_Holiday($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);
        $mMedical    = new M_MedicalCertificate($this->request);

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
            //* Must have at least one sick letter image or medical doc
            if (empty($row->image) && empty($row->image2) && empty($row->image3) && empty($row->img_medical))
                throw new BusinessException("Pengajuan ini belum ada Foto Surat Sakit maupun Keterangan Surat Sakit");

            //* Medical certificate must not be pending
            $docMedical = $mMedical->where('trx_absent_id', $id)
                ->whereIn('docstatus', [$this->DOCSTATUS_Drafted, $this->DOCSTATUS_Inprogress])
                ->first();

            if ($docMedical)
                throw new BusinessException("Pengajuan ini ada surat keterangan sakit yang masih pending dengan nomor : {$docMedical->documentno}");

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
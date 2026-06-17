<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Models\M_Assignment;
use App\Models\M_Attendance;
use App\Models\M_Configuration;
use App\Models\M_EmpBranch;
use App\Models\M_Employee;
use App\Services\BaseServices;
use App\Models\M_Absent;
use App\Models\M_Holiday;
use App\Models\M_WorkDetail;

class AttendanceServices extends BaseServices
{
    public function __construct(int $userID, int $employeeID)
    {
        parent::__construct();

        //* Set User & Employee Session
        $this->userID = $userID;
        $this->employeeID = $employeeID;

        $this->model = new M_Attendance($this->request);
        $this->entity = new \App\Entities\Attendance();
    }

    public function getTodayAttendance(int $employeeID)
    {
        $mAssignment = new M_Assignment($this->request);
        $mConfig = new M_Configuration($this->request);
        $mEmployee = new M_Employee($this->request);
        $mEmpBranch = new M_EmpBranch($this->request);

        $configMNSOD = $mConfig->where('name', 'MANAGER_NO_NEED_SPECIAL_OFFICE_DUTIES')->first();

        if (!$configMNSOD) throw new NotFoundException("Config tidak ditemukan");

        $configMNSOD = $configMNSOD->value == 'Y' ? true : false;
        $lvlManager = 100003;

        $today = date('Y-m-d');

        $employee = $mEmployee->find($employeeID);

        if (!$employee) throw new NotFoundException("Karyawan tidak ditemukan");

        if ($configMNSOD && $employee->md_levelling_id <= $lvlManager) {
            $where = "v_attendance.md_employee_id = {$employeeID}";
            $where .= " AND v_attendance.date = '{$today}'";

            $clockIn = $this->model->getAttendance($where, 'ASC')->getRow();
            $clockOut = $this->model->getAttendance($where, 'DESC')->getRow();
        } else {
            //* Get Employee Assignment
            $whereClause = "DATE(trx_assignment_date.date) = '{$today}'
                    AND trx_assignment.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')
                    AND trx_assignment_detail.md_employee_id = {$employeeID}
                    AND trx_assignment_date.isagree IN ('{$this->LINESTATUS_Approval}', '{$this->LINESTATUS_Realisasi_HRD}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Disetujui}')
                    AND trx_assignment.submissiontype = {$mAssignment->Pengajuan_Penugasan}";

            $officeAssignment = $mAssignment->getDetailData($whereClause)->getRow();

            if ($officeAssignment) {
                $branchIn = $officeAssignment->branch_in_line;
                $branchOut = $officeAssignment->branch_out_line;
            } else {
                $empBranch = $mEmpBranch->where('md_employee_id', $employeeID)->findAll();

                if (!$empBranch) throw new NotFoundException("Data Cabang Karyawan belum diisi");

                $branchIn = implode(", ", array_column($empBranch, 'md_branch_id'));
                $branchOut = $branchIn;
            }

            $where = "v_attendance_branch.md_employee_id = {$employeeID}";
            $where .= " AND v_attendance_branch.date = '{$today}'";

            $clockIn = $this->model->getAttBranch("{$where} AND v_attendance_branch.md_branch_id IN ($branchIn)")->getRow();
            $clockOut = $this->model->getAttBranch("{$where} AND v_attendance_branch.md_branch_id IN ($branchOut)")->getRow();
        }

        $output = [
            'date' => formatDay_idn(date('w')) . ', ' . format_idn($today),
            'clock_in' =>  $clockIn && !empty($clockIn->clock_in) ? $clockIn->clock_in : null,
            'clock_out' => $clockOut && !empty($clockOut->clock_out) ? $clockOut->clock_out : null
        ];

        return $output;
    }

    public function getMonthlyAttendance(int $md_employee_id)
    {
        $mAbsent = new M_Absent($this->request);
        $mHoliday = new M_Holiday($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);

        $firstDate = date('Y-m-01');
        $lastDate = date('Y-m-t');

        $holiday = $mHoliday->getHolidayDate();
        $holidays = implode(", ", $holiday);

        $whereClause = "md_work_detail.isactive = 'Y'";
        $whereClause .= " AND md_employee_work.md_employee_id = {$md_employee_id}";
        $whereClause .= " AND (md_employee_work.validfrom <= '{$firstDate}' AND md_employee_work.validto >= '{$lastDate}')";
        $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getResult();
        $daysOff = getDaysOff($workDetail);
        $daysOffStr = implode(", ", $daysOff);

        // $whereClause = "v_attendance.md_employee_id = {$md_employee_id}";
        // $whereClause .= " AND MONTH(v_attendance.date) = MONTH(CURDATE())";
        // $whereClause .= " AND YEAR(v_attendance.date) = YEAR(CURDATE())";
        // $hadir = count($this->model->getAttendance($whereClause)->getResult());

        $whereClause = "v_attendance.clock_in > '08:00'";
        $whereClause .= " AND v_attendance.md_employee_id = {$md_employee_id}";
        $whereClause .= " AND MONTH(v_attendance.date) = MONTH(CURDATE())";
        $whereClause .= " AND YEAR(v_attendance.date) = YEAR(CURDATE())";
        $whereClause .= " AND DATE_FORMAT(v_attendance.date, '%w') NOT IN ({$daysOffStr})";
        $whereClause .= " AND DATE(v_attendance.date) NOT IN ({$holidays})";
        $terlambat = count($this->model->getAttendance($whereClause)->getResult());

        $baseClause = "v_all_submission.md_employee_id = {$md_employee_id}"
            . " AND v_all_submission.isagree = '{$this->LINESTATUS_Disetujui}'"
            . " AND MONTH(v_all_submission.date) = MONTH(CURDATE())"
            . " AND YEAR(v_all_submission.date) = YEAR(CURDATE())";

        $sakit = count($mAbsent->getAllSubmission(
            $baseClause . " AND v_all_submission.submissiontype = {$mAbsent->Pengajuan_Sakit}"
        )->getResult());

        $cuti = count($mAbsent->getAllSubmission(
            $baseClause . " AND v_all_submission.submissiontype = {$mAbsent->Pengajuan_Cuti}"
        )->getResult());

        $ijin = count($mAbsent->getAllSubmission(
            $baseClause . " AND v_all_submission.submissiontype = {$mAbsent->Pengajuan_Ijin}"
        )->getResult());

        $ijinResmi = count($mAbsent->getAllSubmission(
            $baseClause . " AND v_all_submission.submissiontype = {$mAbsent->Pengajuan_Ijin_Resmi}"
        )->getResult());

        $alpa = count($mAbsent->getAllSubmission(
            $baseClause . " AND v_all_submission.submissiontype = {$mAbsent->Pengajuan_Alpa}"
        )->getResult());

        $tk1Hari = count($mAbsent->getAllSubmission(
            $baseClause . " AND v_all_submission.submissiontype = {$mAbsent->Pengajuan_Tugas_Kantor}"
        )->getResult());

        $penugasan = count($mAbsent->getAllSubmission(
            $baseClause . " AND v_all_submission.submissiontype = {$mAbsent->Pengajuan_Penugasan}"
        )->getResult());

        $tkSetengahHari = count($mAbsent->getAllSubmission(
            $baseClause . " AND v_all_submission.submissiontype = {$mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari}"
        )->getResult());

        $pulangCepat = count($mAbsent->getAllSubmission(
            $baseClause . " AND v_all_submission.submissiontype = {$mAbsent->Pengajuan_Pulang_Cepat}"
        )->getResult());

        $lupaAbsenMasuk = count($mAbsent->getAllSubmission(
            $baseClause . " AND v_all_submission.submissiontype = {$mAbsent->Pengajuan_Lupa_Absen_Masuk}"
        )->getResult());

        $lupaAbsenPulang = count($mAbsent->getAllSubmission(
            $baseClause . " AND v_all_submission.submissiontype = {$mAbsent->Pengajuan_Lupa_Absen_Pulang}"
        )->getResult());

        $periodParts = explode(' ', format_idn(date('Y-m-01')));
        array_shift($periodParts);

        return [
            'period'            => implode(' ', $periodParts),
            // 'hadir'             => $hadir,
            'terlambat'         => $terlambat,
            'sakit'             => $sakit,
            'cuti'              => $cuti,
            'ijin'              => $ijin,
            'ijin_resmi'        => $ijinResmi,
            'alpa'              => $alpa,
            'tk_1_hari'         => $tk1Hari,
            'penugasan'         => $penugasan,
            'tk_setengah_hari'  => $tkSetengahHari,
            'pulang_cepat'      => $pulangCepat,
            'lupa_absen_masuk'  => $lupaAbsenMasuk,
            'lupa_absen_pulang' => $lupaAbsenPulang,
        ];
    }

    public function getInProgressSubmissions($employeeId)
    {
        $mAbsent = new M_Absent($this->request);
        
        $baseClause = "v_all_submission.md_employee_id = {$employeeId}"
            . " AND v_all_submission.docstatus = '{$this->DOCSTATUS_Inprogress}'";

        $submissions = $mAbsent->getAllSubmission($baseClause)->getResult();

        $seen = [];
        $result = [];
        foreach ($submissions as $submission) {
            if (!in_array($submission->documentno, $seen)) {
                $seen[] = $submission->documentno;
                $result[] = [
                    'id'         => (int) $submission->header_id,
                    'documentno' => $submission->documentno,
                ];
            }
        }

        return $result;
    }
}

<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Models\M_Assignment;
use App\Models\M_Attendance;
use App\Models\M_Configuration;
use App\Models\M_EmpBranch;
use App\Models\M_Employee;
use App\Services\BaseServices;

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
}

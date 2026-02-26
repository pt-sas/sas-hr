<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Employee;
use App\Models\M_Attendance;
use App\Models\M_AbsentDetail;
use App\Models\M_Absent;
use App\Models\M_EmpWorkDay;
use App\Models\M_WorkDetail;
use App\Models\M_Holiday;

use App\Models\M_AccessMenu;

use Config\Services;

class NoAttendance extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Attendance($this->request);
        $this->entity = new \App\Entities\Attendance();
    }

    public function reportIndex()
    {
        $date = format_dmy(date('Y-m-d'), "-");
        $data = ['date_range' => $date . ' - ' . $date];
        return $this->template->render('report/noattendance/v_no_attendance', $data);
    }

    public function reportShowAll()
    {
        $post = $this->request->getVar();

        $mEmployee   = new M_Employee($this->request);
        $mAttendance = new M_Attendance($this->request);
        $mSubmission = new M_AbsentDetail($this->request);
        $mAbsent     = new M_Absent($this->request);
        $mEmpWork    = new M_EmpWorkDay($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);
        $mHoliday    = new M_Holiday($this->request);

        $mAccess = new M_AccessMenu($this->request);

        $recordTotal = 0;
        $recordsFiltered = 0;
        $data = [];
        $finalData = [];
        
        if ($this->request->getMethod(true) === 'POST') {
            if (isset($post['form']) && $post['clear'] === 'false') {
        
        // 1 Parse dan validasi filter
        $startDate = null;
        $endDate   = null;

        foreach ($post['form'] as $f) {
            if ($f['name'] === 'date' && !empty($f['value'])) {
                [$rawStart, $rawEnd] = array_map(
                    'trim',
                    explode(' - ', urldecode($f['value']))
                );
                $startDate = date('Y-m-d', strtotime($rawStart));
                $endDate   = date('Y-m-d', strtotime($rawEnd));
            }
        }


        // 1.5 Buat filter untuk branch/division dari view
        $branchIds   = [];
        $divisionIds = [];

        foreach ($post['form'] as $f) {

            if (isset($f['name'])) {
                $name = $f['name'];
            } else {
                $name = null;
            }

            if (isset($f['value'])) {
                $value = $f['value'];
            } else {
                $value = null;
            }

            if ($name === 'md_branch_id' && !empty($value)) {
                foreach ((array) $value as $v) {
                    if ($v !== '') {
                        $branchIds[] = $v;
                    }
                }
            }

            if ($name === 'md_division_id' && !empty($value)) {
                foreach ((array) $value as $v) {
                    if ($v !== '') {
                        $divisionIds[] = $v;
                    }
                }
            }
        }

         /**
         * Mendapatkan Hak Akses Karyawan di session log-in sekarang
         */

        // Ambil user yang sedang login sekarang, lalu cek apakah memiliki W_Emp_All_Data
        $roleEmp = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_All_Data');

        // Branch/Division apa saja yang bisa diakses user ini
        $arrAccess = $mAccess->getAccess($this->session->get("sys_user_id"));

        // Employee siapa saja yang dibawah user ini yang sedang login
        $arrEmployee = $mEmployee->getChartEmployee($this->session->get('md_employee_id'));

        // Menyimpan nilai akhir employee siapa saja yang akan tampil saat retrieve table
        $employeeId = [];

        // Jika ada ArrAccess dan ada value untuk branch dan division maka lanjut
        if ($arrAccess && isset($arrAccess["branch"]) && isset($arrAccess["division"])) {

            // Masukkin branch dan division yang didapat kedalam array
            $arrBranch = $arrAccess["branch"];
            $arrDiv = $arrAccess["division"];

            // Ambil data employee dengan parameter branch/Id dari si user
            $arrEmpBased = $mEmployee->getEmployeeBased($arrBranch, $arrDiv);

            // Klo punya W_Emp_All_Data maka...
            if ($roleEmp && !empty($this->session->get('md_employee_id'))) {

                // Set allowedemployeeIds menjadi berdasarkan arrempbased (yang menampung data employee berdasarkan branch dan division user) dan arremployee
                $employeeId = array_unique(array_merge($arrEmpBased, $arrEmployee));

            } else if (!$roleEmp && !empty($this->session->get('md_employee_id'))) {

                // Klo ga ada roleemp maka cari employee dibawah current user
                $employeeId = $arrEmployee;

            } else if ($roleEmp && empty($this->session->get('md_employee_id'))) {

                $employeeId = $arrEmpBased;

            } else {

                $employeeId = [$this->session->get('md_employee_id')];
            }

        } else if (!empty($this->session->get('md_employee_id'))) {

            $employeeId = $arrEmployee;

        } else {

            $employeeId = [$this->session->get('md_employee_id')];
        }

        $employeeId = array_unique($employeeId);

        $branchIds   = array_values(array_unique($branchIds));
        $divisionIds = array_values(array_unique($divisionIds));



        // 2 Base dari tabel
        $employees = $mEmployee
            ->select('
                md_employee.md_employee_id,
                md_employee.nik,
                md_employee.fullname,
                GROUP_CONCAT(DISTINCT md_branch.description ORDER BY md_branch.description SEPARATOR ", ") AS branch_name,
                GROUP_CONCAT(DISTINCT md_division.description ORDER BY md_division.description SEPARATOR ", ") AS division_name
            ')
            ->join('md_employee_branch', 'md_employee_branch.md_employee_id = md_employee.md_employee_id', 'left')
            ->join('md_branch', 'md_branch.md_branch_id = md_employee_branch.md_branch_id', 'left')
            ->join('md_employee_division', 'md_employee_division.md_employee_id = md_employee.md_employee_id', 'left')
            ->join('md_division', 'md_division.md_division_id = md_employee_division.md_division_id', 'left')
            ->where('md_employee.isactive', 'Y');

            if (!empty($branchIds)) {
                $mEmployee->whereIn('md_employee_branch.md_branch_id', $branchIds);
            }

            if (!empty($divisionIds)) {
                $mEmployee->whereIn('md_employee_division.md_division_id', $divisionIds);
            }

            if (!empty($employeeId)) {
                $mEmployee->whereIn('md_employee.md_employee_id', $employeeId);
            }

            $mEmployee->groupBy('md_employee.md_employee_id');

            $employees = $mEmployee->findAll();
        
        // 3 Buat domain tanggal
        $dates = [];
        $period = new \DatePeriod(
            new \DateTime($startDate),
            new \DateInterval('P1D'),
            (new \DateTime($endDate))->modify('+1 day')
        );

        foreach ($period as $d) {
            $dates[] = $d->format('Y-m-d');
        }

        // 4 Load data filter/referensi
        $holidays = $mHoliday->getHolidayDate();

        $attendanceMap = [];
        $attendances = $mAttendance->getAttendance([
            'DATE(v_attendance.date) >=' => $startDate,
            'DATE(v_attendance.date) <=' => $endDate
        ], 'ASC');

        foreach ($attendances->getResult() as $a) {
            $attendanceMap[$a->nik][$a->date] = true;
        }
        
        $absentMap = [];
        $absents = $mSubmission
            ->select('
            trx_absent.md_employee_id,
            DATE(trx_absent_detail.date) AS absent_date
            ')
            ->join(
                'trx_absent',
                'trx_absent.trx_absent_id = trx_absent_detail.trx_absent_id',
                'inner'
            )
            ->where('trx_absent_detail.date >=', $startDate)
            ->where('trx_absent_detail.date <=', $endDate)
            ->whereIn('trx_absent.docstatus', ['CO', 'IP'])
            ->where('trx_absent.isapproved', 'Y')
            ->where('trx_absent_detail.isagree', 'Y')
            ->findAll();

            foreach ($absents as $ab) {
                $empId = is_array($ab)
                    ? $ab['md_employee_id']
                    : $ab->md_employee_id;

                $date = is_array($ab)
                    ? $ab['absent_date']
                    : $ab->absent_date;

                $absentMap[$empId][$date] = true;
            }


        //5 Operasi where dan if filter dikerjakan
        $data = [];

        foreach ($employees as $emp) {

            // load work schedule
            $workDay = $mEmpWork->where([
                'md_employee_id' => $emp->md_employee_id,
                'validfrom <='   => $startDate,
                'validto >='     => $endDate,
                'isactive'       => 'Y'
            ])->first();

            if (!$workDay) continue;

            $workingDays = [];
            foreach (
                $mWorkDetail
                    ->where('md_work_id', $workDay->md_work_id)
                    ->where('isactive', 'Y')
                    ->findAll()
                as $wd
            ) {
                $workingDays[] = $wd->md_day_id == 7 ? 0 : (int)$wd->md_day_id;
            }

            foreach ($dates as $date) {

                if (in_array($date, $holidays, true)) continue;
                if (!in_array((int)date('w', strtotime($date)), $workingDays, true)) continue;
                if (isset($attendanceMap[$emp->nik][$date])) continue;
                if (isset($absentMap[$emp->md_employee_id][$date])) continue;

                $data[] = [
                    $emp->nik,
                    $emp->fullname,
                    $emp->branch_name ?? '-',
                    $emp->division_name ?? '-',
                    format_dmy($date, '-')
                ];
            }
        }

        // 6 Datatables pagination
        $start = (int) $this->request->getPost('start') ?? 0;
        $number = $start;

        // Dulu disini ada finalData, pindah ke atas

        foreach ($data as $row) {
            $number++;

            $finalData[] = [
                $number,        // row number
                $row[0],        // nik
                $row[1],        // fullname
                $row[2],        // branch
                $row[3],        // division
                $row[4],        // date
            ];
        }

        $recordTotal    = count($finalData);
        $recordsFiltered = $recordTotal;

        } //isset end

        // 7 Balikkin datatables json
        $result = [
            'draw'            => $this->request->getPost('draw'),
            'recordsTotal'    => $recordTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $finalData
        ];

        return $this->response->setJSON($result);
            
        } // getMethod post end
    } // reportShowAll end
}
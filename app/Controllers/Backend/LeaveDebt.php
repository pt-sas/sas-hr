<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Employee;
use App\Models\M_LeaveBalance;
use App\Models\M_Transaction;
use App\Models\M_MassLeave;
use App\Models\M_AssignmentDate;
use App\Models\M_AssignmentDetail;
use App\Models\M_Assignment;

use App\Models\M_AccessMenu;

use Config\Services;

class LeaveDebt extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Transaction($this->request);
    }

    public function index()
    {
        $data = [
            'year'          => date('Y')
        ];

        return $this->template->render('report/leavedebt/v_leave_debt', $data);
    }

    public function reportShowAll()
    {
        $post = $this->request->getVar();

        $mEmployee   = new M_Employee($this->request);
        $mLeaveBalance = new M_LeaveBalance($this->request);
        $mMassLeave = new M_MassLeave($this->request);
        $mAssignmentDate = new M_AssignmentDate($this->request);
        $mAssignmentDetail = new M_AssignmentDetail($this->request);
        $mAssignment = new M_Assignment($this->request);


        $mAccess = new M_AccessMenu($this->request);

        $finalData = [];
        $recordTotal = 0;
        $recordsFiltered = 0;

        if ($this->request->getMethod(true) === 'POST') {
            if (isset($post['form']) && $post['clear'] === 'false') {

                
        // 1 Buat filter untuk branch/division/employee dari view

        // Persiapkan array kosong untuk menyimpan id2 parameter yang diterima dari form
        $branchIds   = [];
        $divisionIds = [];
        $employeeId = [];

        // Persiapkan 2 variabel kosong untuk $year nantinya
        $startDate = null;
        $endDate   = null;

        foreach ($post['form'] as $f) {

            if ($f['name'] === 'year' && !empty($f['value'])) { // Jika name itu year dan value tidak kosong maka lanjut
            $year = (int) $f['value']; // Simpan value ke year
            $startDate = $year . '-01-01'; // Simpan year yang di append ke -01-01 sebagai startdate
            $endDate   = date('Y-m-d'); // Enddate adalah tanggal sekarang
            }

            // Simpan id branch kedalam branchIds jika name nya md_branch_id
            if ($f['name'] === 'md_branch_id' && !empty($f['value'])) {
                $branchIds = array_merge($branchIds, (array) $f['value']); // Pake array merge karena ini bisa multiple value dibanding $year yang hanya bisa 1
            }

            // Simpan id division kedalam divisionIds jika namanya md_division_id
            if ($f['name'] === 'md_division_id' && !empty($f['value'])) {
                $divisionIds = array_merge($divisionIds, (array) $f['value']); // Pake array merge karena ini bisa multiple value dibanding $year yang hanya bisa 1
            }

            // Simpan id employee kedalam employeeIds jika namanya md_employee_id
            if ($f['name'] === 'md_employee_id' && !empty($f['value'])) {
                $employeeId = array_merge($employeeId, (array) $f['value']); // Pake array merge karena ini bisa multiple value dibanding $year yang hanya bisa 1
                
            } else if ($f['name'] === 'md_employee_id' && empty($f['value'])) {

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
            }

        }

        // Guard supaya intinya jika filter $year tidak berhasil dikirim dan startdate/enddate jadi tidak terinisialisasi, maka langsung return
        if (!$startDate || !$endDate) {
            return $this->response->setJSON([
                'draw' => $this->request->getPost('draw') ?? 1,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ]);
        }

        // 1 selesai


        // 2 Buat select query untuk output

        $employees = $mEmployee // Gunakan employeemodel untuk bangun query
            // select id, nik, fullname, branch, division
            ->select('
                md_employee.md_employee_id,
                md_employee.nik,
                md_employee.fullname,
                md_employee.registerdate,
                GROUP_CONCAT(DISTINCT md_branch.description ORDER BY md_branch.description SEPARATOR ", ") AS branch_name,
                GROUP_CONCAT(DISTINCT md_division.description ORDER BY md_division.description SEPARATOR ", ") AS division_name
            ') // untuk branch dan division gunakan group concat supaya dapat menggabungkan multiple branch atau division rows per employee bila ada menjadi 1 string yang dipisah koma
            
            ->join('md_employee_branch', 'md_employee_branch.md_employee_id = md_employee.md_employee_id', 'left') 
            // Left join md_employee ke md_employee_branch supaya bisa dapat semua id branch dimana employee bekerja

            ->join('md_branch', 'md_branch.md_branch_id = md_employee_branch.md_branch_id', 'left')
            // Left join ke md_branch juga supaya bisa dapat semua md_branch.description yang sesuai per employee

            ->join('md_employee_division', 'md_employee_division.md_employee_id = md_employee.md_employee_id', 'left')
            // Left join md_employee ke md_employee_division supaya bisa dapat semua id division dimana employee bekerja

            ->join('md_division', 'md_division.md_division_id = md_employee_division.md_division_id', 'left')
            // Join ke division supaya bisa dapat md_division.description

            ->where('md_employee.isactive', 'Y');

            // Jika terdapat filter branchIds, maka tambah sebuah where yang memastikan bahwa data yang di select itu hanya berasal dari branch yang di filter
            if (!empty($branchIds)) {
                $mEmployee->whereIn('md_employee_branch.md_branch_id', $branchIds);
            }

            // Jika terdapat filter divisionIds, maka tambah sebuah where yang memastikan bahwa data yang di select itu hanya berasal dari division yang di filter
            if (!empty($divisionIds)) {
                $mEmployee->whereIn('md_employee_division.md_division_id', $divisionIds);
            }

            // Jika ada filter employeeid maka tambah where dimana employee yg di select sesuai dengan $employeeid
            if (!empty($employeeId)) {
                $mEmployee->whereIn('md_employee.md_employee_id', $employeeId);
            }
            
            // Cek untuk hak akses
            // if (!empty($allowedEmployeeIds)) {
            //     $mEmployee->whereIn('md_employee.md_employee_id', $allowedEmployeeIds);
            // }

            $mEmployee->groupBy('md_employee.md_employee_id');
            $employees = $mEmployee->findAll(); // findAll untuk execute query sesudah di build di atas

        // 2 selesai
        

        // 3 Perhitungan massleave

        $start = $this->request->getPost('start');
        // Pagination untuk lihat dari mana kolom No di table output mulai menghitung, jika ada value di start maka gunakan value itu, jika tidak ada maka mulai dari 0 (Biar ngikut per page)

        $today = date('Y-m-d');

        foreach ($employees as $emp) {
            // Get emp info dari semua data row employee didalam $employees
            // $employee = $mEmployee->find($emp->md_employee_id);
            // if (!$employee) continue; // Kalau tidak ada maka skip

            // Ambil tahun dimana employee register pertama
            $registerDate = $emp->registerdate;

            // registerdate bisa null jadi perlu buat cek ini
            if ($registerDate) {
                $registerYear = (int) date('Y', strtotime($registerDate));
            } else {
                $registerYear = null;
            }


            // Skip employee yang gabung sesudah tahun filter, tidak tampil di output
            if ($registerYear > $year||$registerYear == null) continue;

            $start++;
            // Increment No table row number

            // Saldo employee sesuai tahun filter, gunakan fungsi yang tersedia dengan parameter yang sesuai
            $balanceRow = $mLeaveBalance->getTotalBalance($emp->md_employee_id, $year);
            
            if ($balanceRow) {
                $leaveBalance = (int) $balanceRow->balance;
            } else {
                $leaveBalance = 0;
            }


            // $massLeaveStart = ($statusId === 100004 && $registerYear === $year)
            //     ? $registerDate
            //     : (($registerDate && $registerYear === $year)
            //         ? $registerDate
            //         : $year . '-01-01');

            // Tentuin massLeaveStart
            // Jika employee join atau resign di tahun yang sama maka massleavestart di registerdate, selain itu mulai di januari 1 pada tahun itu
            if ($registerYear === $year) {
                $massLeaveStart = $registerDate;
            } else {
                $massLeaveStart = $year . '-01-01';
            }

            // Buat kondisi where dalam lingkup $year
            // $where = [
            //     'trx_assignment_detail.md_employee_id' => $emp->md_employee_id,
            //     'trx_assignment_date.isactive'         => 'Y',
            //     'trx_assignment_date.isagree'          => 'Y',
            //     'trx_assignment.isapproved'            => 'Y',
            //     'trx_assignment.docstatus'             => ('CO'||'IP'),
            //     'YEAR(trx_assignment_date.date)'       => $year
            // ];

            $where = "trx_assignment_detail.md_employee_id = {$emp->md_employee_id}
            AND trx_assignment_date.isactive = 'Y'
            AND trx_assignment_date.isagree = 'Y'
            AND trx_assignment.isapproved = 'Y'
            AND trx_assignment.docstatus in ('CO','IP')
            AND YEAR(trx_assignment_date.date) = {$year}
            ";

            // Ambil semua assignment utk employee ini dalam 1 tahun sesuai $where
            $assignmentRows = $mAssignment->getDetailData($where)
                ->getResultArray();

            // Ambil array semua date assignment dari assignmentRows
            $assignmentDates = [];
            foreach ($assignmentRows as $r) {
                $assignmentDates[] = $r['date'];
            }

            // Ambil massleave table dengan parameter yang sudah disesuaikan untuk employee yang sedang di loop
            $massLeaveModelQuery = $mMassLeave
                ->where('isactive', 'Y')
                ->where('isaffect', 'Y')
                ->where('startdate >=', $massLeaveStart)
                ->where('startdate <=', min($year . '-12-31', $today));

            // Hanya massleave dimana startdate tidak ada di assignmentdates
            if (!empty($assignmentDates)) {
                $massLeaveModelQuery->whereNotIn('startdate', $assignmentDates);
            }

            $massLeaveCount = $massLeaveModelQuery->countAllResults();

            // debt remain
            $debtRemain = $leaveBalance - $massLeaveCount;
            if ($massLeaveCount === 0 && $leaveBalance <= 0) {
                $debtRemain = 0;
            }

            $finalData[] = [
                $start,
                $emp->nik,
                $emp->fullname,
                $year,
                $emp->branch_name ?? '-',
                $emp->division_name ?? '-',
                $leaveBalance,
                $massLeaveCount,
                $debtRemain,
            ];
        }

        $recordTotal = count($finalData);
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
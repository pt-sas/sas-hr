<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_AllowanceAtt;
use App\Models\M_Attendance;
use App\Models\M_EmpBranch;
use App\Models\M_EmpDivision;
use App\Models\M_Employee;
use App\Models\M_Holiday;
use App\Models\M_Levelling;
use App\Models\M_Absent;
use App\Models\M_AbsentDetail;
use App\Models\M_Configuration;
use App\Models\M_EmpWorkDay;
use App\Models\M_Assignment;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Style_Alignment;
use PHPExcel_Style_Border;
use PHPExcel_Cell_DataType;
use PHPExcel_Style_Fill;
use PHPExcel_Worksheet_PageSetup;
use DateTime;
use Config\Services;

class AllowanceAtt extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_AllowanceAtt($this->request);
        $this->entity = new \App\Entities\AllowanceAtt();
    }

    public function reportIndex()
    {
        $date = format_dmy(date('Y-m-d'), "-");
        $data = [
            'date_range' => $date . ' - ' . $date
        ];

        return $this->template->render('report/allowance/v_rpt_allowance', $data);
    }

    public function reportShowAll()
    {
        $mHoliday = new M_Holiday($this->request);
        $mEmployee = new M_Employee($this->request);
        $mEmpBranch = new M_EmpBranch($this->request);
        $mEmpDiv = new M_EmpDivision($this->request);
        $mAttendance = new M_Attendance($this->request);

        $post = $this->request->getVar();
        $data = [];

        $recordTotal = 0;
        $recordsFiltered = 0;

        if ($this->request->getMethod(true) === 'POST') {
            if (isset($post['form']) && $post['clear'] === 'false') {
                $table = $mEmployee->table;
                $select = $mEmployee->findAll();
                $order = $this->request->getPost('columns');
                $sort = ['fullname' => 'ASC'];
                $search = $this->request->getPost('search');
                $where['md_employee.isactive'] = 'Y';

                foreach ($post['form'] as $value) {
                    if (!empty($value['value'])) {
                        if ($value['name'] === "submissiondate") {
                            $datetime = urldecode($value['value']);
                            $date = explode(" - ", $datetime);
                        }

                        if ($value['name'] === "md_division_id") {
                            $arrDiv_id = $value['value'];

                            $listDiv = $mEmpDiv->whereIn("md_division_id", $arrDiv_id)->findAll();
                            $where['md_employee.md_employee_id'] = [
                                'value'     => array_column($listDiv, "md_employee_id")
                            ];
                        }

                        if ($value['name'] === "md_branch_id") {
                            $arrBranch_id = $value['value'];

                            $listBranch = $mEmpBranch->whereIn("md_branch_id", $arrBranch_id)->findAll();
                            $where['md_employee.md_employee_id'] = [
                                'value'     => array_column($listBranch, "md_employee_id")
                            ];
                        }
                    }
                }

                $start_date = date("Y-m-d", strtotime($date[0]));
                $end_date = date("Y-m-d", strtotime($date[1]));
                $holiday = $mHoliday->getHolidayDate();

                $date_range = getDatesFromRange($start_date, $end_date, $holiday);

                $number = $this->request->getPost('start');
                $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, [], $where);

                foreach ($date_range as $value) :
                    foreach ($list as $val) :
                        $row = [];
                        $qty = 0;

                        $parAllow = [
                            'trx_allow_attendance.md_employee_id'    => $val->md_employee_id,
                            'trx_allow_attendance.submissiondate'    => $value
                        ];

                        $attendance = $mAttendance->getAttendance(['trx_attendance.nik' => $val->nik, 'trx_attendance.date' => $value])->getRow();

                        if (isset($attendance)) {
                            if ($attendance->absent === 'Y') {
                                $qty = 1;
                            }
                        }

                        $allow = $this->model->getAllowance($parAllow)->getRow();

                        $number++;
                        $row[] = $number;
                        $row[] = $allow ? $allow->documentno : "";
                        $row[] = $val->fullname;
                        $row[] = format_dmy($value, "-");
                        $row[] = $allow ? $allow->submissiontype : "";
                        $row[] = $allow ? ($qty - $allow->amount) : $qty;
                        $row[] = $allow ? $allow->reason : "";
                        $data[] = $row;
                    endforeach;
                endforeach;

                $recordTotal = count($data);
                $recordsFiltered = count($data);
            }

            $result = [
                'draw'              => $this->request->getPost('draw'),
                'recordsTotal'      => $recordTotal,
                'recordsFiltered'   => $recordsFiltered,
                'data'              => $data
            ];

            return $this->response->setJSON($result);
        }
    }

    public function index()
    {
        $data = [
            'month' => date('M-Y'),
        ];

        return $this->template->render('report/allowance/v_report_allowance', $data);
    }

    public function reportAllNew()
    {
        $post = $this->request->getPost();

        $mEmployee = new M_Employee($this->request);
        $mLevel = new M_Levelling($this->request);
        $mAttendance = new M_Attendance($this->request);
        $mAbsent = new M_Absent($this->request);
        $mAssignment = new M_Assignment($this->request);
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mHoliday = new M_Holiday($this->request);
        $mAbsentDetail = new M_AbsentDetail($this->request);
        $mEmpBranch = new M_EmpBranch($this->request);
        $mAllowance = new M_AllowanceAtt($this->request);
        $mConfig = new M_Configuration($this->request);

        $md_branch_id = null;
        $md_division_id = null;
        $md_employee_id = null;

        if (isset($post['md_branch_id']))
            $md_branch_id = $post['md_branch_id'];

        if (isset($post['md_division_id']))
            $md_division_id = $post['md_division_id'];

        if (isset($post['md_employee_id']) && $post['md_employee_id']) {
            $md_employee_id = $post['md_employee_id'];
        } else {
            $md_employee_id = $this->access->getEmployeeData();
        }

        // Panggil class PHPExcel nya
        $excel = new PHPExcel();
        // Settingan awal file excel
        $excel->getProperties()->setCreator('Laporan Saldo TKH')
            ->setLastModifiedBy('Laporan Saldo TKH')
            ->setTitle("Laporan Saldo TKH")
            ->setSubject("Laporan Saldo TKH")
            ->setDescription("Laporan Saldo TKH")
            ->setKeywords("Laporan Saldo TKH");

        // Style untuk header kolom (teks bold dan rata tengah)
        $style_col = $this->createBorderStyle(true, true);

        // Style untuk baris normal
        $style_row = $this->createBorderStyle();

        // Style untuk baris hari libur
        $style_row_dayoff = $this->createBorderStyle(false, false, 'FF0000');

        $sheet = $excel->setActiveSheetIndex(0);

        //** This set header report */
        $sheet->setCellValue('A1', "LAPORAN ABSENSI HARIAN");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        //TODO : Header kolom dan merge cell
        $headers = [
            'A3' => ['text' => 'No', 'merge' => 'A3:A4'],
            'B3' => ['text' => 'NIK', 'merge' => 'B3:B4'],
            'C3' => ['text' => 'Nama', 'merge' => 'C3:C4'],
            'D3' => ['text' => 'Jabatan', 'merge' => 'D3:D4']
        ];

        foreach ($headers as $cell => $info) {
            $sheet->setCellValue($cell, $info['text']);
            $sheet->mergeCells($info['merge']);
            $sheet->getStyle($info['merge'])->applyFromArray($style_col);
        }

        $periode = $post['periode'];
        $config = $mConfig->where('name', 'DAY_CUT_OFF_ALLOWANCE')->first();
        $cutOff = $config->value;

        // TODO : Set Previous Month Date Header
        $prevMonth = date('Y-m-' . ($cutOff + 1), strtotime($periode . '-1 month'));
        $prevMonthEnd = date('Y-m-t', strtotime($prevMonth));
        $prevMonthYear = date('M-Y', strtotime($prevMonth));
        $prevDateRange = getDatesFromRange($prevMonth, $prevMonthEnd, [], 'Y-m-d', 'all');

        // TODO : Set kolom E3 dengan tulisan "Bulan"
        $sheet->setCellValue('E3', $prevMonthYear);

        $cell = 'E';
        $cellRow = 4;

        $firstCell = '';
        $lastCell = '';
        foreach ($prevDateRange as $date) {
            $date = date('d', strtotime($date));

            $sheet->setCellValue($cell . $cellRow, $date);
            $sheet->getStyle($cell . $cellRow)->applyFromArray($style_col);

            if (count($prevDateRange) - 1)
                $lastCell = $cell;

            $cell++;
        }

        $sheet->mergeCells('E3:' . $lastCell . '3'); // Set Merge Cell
        $sheet->getStyle('E3:' . $lastCell . '3')->applyFromArray($style_col);

        // TODO : Set This Periode Date Header
        $firstDayMonth = date('Y-m-d', strtotime($periode));
        $nowCutMonth = date('Y-m-d', strtotime($firstDayMonth . '+' . ($cutOff - 1) . 'day'));
        $monthYear = date('M-Y', strtotime($firstDayMonth));
        $dateRange = getDatesFromRange($firstDayMonth, $nowCutMonth, [], 'Y-m-d', 'all');

        $firstCell = $cell;

        //TODO : Set kolom terakhir dengan tulisan "Bulan Next"
        $sheet->setCellValue($firstCell . '3', $monthYear);

        foreach ($dateRange as $date) {
            $date = date('d', strtotime($date));

            $sheet->setCellValue($cell . $cellRow, $date);
            $sheet->getStyle($cell . $cellRow)->applyFromArray($style_col);

            if (count($dateRange) - 1)
                $lastCell = $cell;

            $cell++;
        }

        $sheet->mergeCells($firstCell . '3:' . $lastCell . '3'); // Set Merge Cell
        $sheet->getStyle($firstCell . '3:' . $lastCell . '3')->applyFromArray($style_col);

        // TODO : Set Kolom Total
        $sheet->setCellValue($cell . '3', "Total"); // Set kolom E3 dengan tulisan "Bulan"
        $sheet->mergeCells($cell . '3:' . $cell . '4')->getStyle($cell . '3:' . $cell . '4')->applyFromArray($style_col);

        // TODO : Merger Cell pertama sampai ke akhir cell untuk Header
        $sheet->mergeCells('A1:' . $cell . '1');

        //** This getting to body report */
        $builder = $mEmployee
            ->distinct()
            ->select('md_employee.*')
            ->where([
                'md_employee.isactive'        => 'Y',
                'md_employee.md_status_id <>' => 100004
            ]);

        if (!empty($md_employee_id)) {
            $builder->whereIn('md_employee.md_employee_id', (array) $md_employee_id);
            $builder->whereIn('md_employee.md_status_id', [100001, 100002]);
        }

        if (!empty($md_branch_id)) {
            $builder->join(
                'md_employee_branch eb',
                'eb.md_employee_id = md_employee.md_employee_id',
                'left'
            );
            $builder->whereIn('eb.md_branch_id', (array) $md_branch_id);
        }

        if (!empty($md_division_id)) {
            $builder->join(
                'md_employee_division ed',
                'ed.md_employee_id = md_employee.md_employee_id',
                'left'
            );
            $builder->whereIn('ed.md_division_id', (array) $md_division_id);
        }

        $sql = $builder->orderBy('md_employee.fullname', 'ASC')->findAll();

        $holiday = $mHoliday->getHolidayDate();
        $totalDateRange = getDatesFromRange($prevMonth, $nowCutMonth, [], 'Y-m-d', 'all');

        // TODO : Getting Configuration Manager not need Special Office Duties
        $configMNSOD = $mConfig->where('name', 'MANAGER_NO_NEED_SPECIAL_OFFICE_DUTIES')->first();

        $configMNSOD = $configMNSOD->value == 'Y' ? true : false;
        $lvlManager = 100003;

        // TODO : Get All Assignment
        $whereClause = "DATE(v_all_submission.date) BETWEEN '{$prevMonth}' AND '{$nowCutMonth}'";
        $whereClause .= " AND v_all_submission.isagree = '{$this->LINESTATUS_Disetujui}'";
        $whereClause .= " AND v_all_submission.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')";
        $whereClause .= " AND v_all_submission.submissiontype IN (100007, 100008, 100009)";

        $data = $mAbsent->getAllSubmission($whereClause)->getResult();
        $allAssignment = [];

        foreach ($data as $val) {
            $allAssignment[$val->md_employee_id][date('Y-m-d', strtotime($val->date))] = $val;
        }

        // TODO : Get All Attendance
        $whereClause = "v_attendance.date BETWEEN '{$prevMonth}' AND '{$nowCutMonth}'";
        $data = $mAttendance->getAttendance($whereClause)->getResult();
        $allAttendance = [];

        foreach ($data as $val) {
            $allAttendance[$val->md_employee_id][date('Y-m-d', strtotime($val->date))] = $val;
        }

        // TODO : get All Tugas Kunjungan
        $whereClause = "DATE(trx_assignment_date.date) BETWEEN '{$prevMonth}' AND '{$nowCutMonth}'
                    AND trx_assignment.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')
                    AND trx_assignment_date.isagree = '{$this->LINESTATUS_Disetujui}'
                    AND trx_assignment.submissiontype = {$mAssignment->Pengajuan_Penugasan}";
        $data = $mAssignment->getDetailData($whereClause)->getResult();
        $allTugasKunjungan = [];

        foreach ($data as $val) {
            $allTugasKunjungan[$val->karyawan_id][date('Y-m-d', strtotime($val->date))] = $val;
        }

        // TODO : Get All Submission Forgot Absent In
        $whereClause = "DATE(v_all_submission.date) BETWEEN '{$prevMonth}' AND '{$nowCutMonth}'
                        AND v_all_submission.isagree = '{$this->LINESTATUS_Disetujui}'
                        AND v_all_submission.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')
                        AND v_all_submission.submissiontype = {$mAbsent->Pengajuan_Lupa_Absen_Masuk}";
        $data = $mAbsent->getAllSubmission($whereClause)->getResult();
        $allForgetAbsentIn = [];

        foreach ($data as $val) {
            $allForgetAbsentIn[$val->md_employee_id][date('Y-m-d', strtotime($val->date))] = $val;
        }

        // TODO : Get All Submission Forgot Absent Out
        $whereClause = "DATE(v_all_submission.date) BETWEEN '{$prevMonth}' AND '{$nowCutMonth}'
                        AND v_all_submission.isagree = '{$this->LINESTATUS_Disetujui}'
                        AND v_all_submission.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')
                        AND v_all_submission.submissiontype = {$mAbsent->Pengajuan_Lupa_Absen_Pulang}";

        $data = $mAbsent->getAllSubmission($whereClause)->getResult();
        $allForgetAbsentOut = [];

        foreach ($data as $val) {
            $allForgetAbsentOut[$val->md_employee_id][date('Y-m-d', strtotime($val->date))] = $val;
        }

        // TODO : Get All Submission Permission Submission Leave Early
        $whereClause = "DATE(v_all_submission.date) BETWEEN '{$prevMonth}' AND '{$nowCutMonth}'
                        AND v_all_submission.isagree = '{$this->LINESTATUS_Disetujui}'
                        AND v_all_submission.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')
                        AND v_all_submission.submissiontype = {$mAbsent->Pengajuan_Pulang_Cepat}";

        $data = $mAbsent->getAllSubmission($whereClause)->getResult();
        $allLeaveEarly = [];

        foreach ($data as $val) {
            $allLeaveEarly[$val->md_employee_id][date('Y-m-d', strtotime($val->date))] = $val;
        }

        // TODO : Get All Submission Half Day Office Duties
        $whereClause = "DATE(trx_absent_detail.date) BETWEEN '{$prevMonth}' AND '{$nowCutMonth}'
                    AND trx_absent.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')
                    AND trx_absent_detail.isagree = '{$this->LINESTATUS_Disetujui}'
                    AND trx_absent.submissiontype = {$mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari}";

        $data = $mAbsentDetail->getAbsentDetail($whereClause)->getResult();
        $allOfficeHalfDay = [];

        foreach ($data as $val) {
            $allOfficeHalfDay[$val->md_employee_id][date('Y-m-d', strtotime($val->date))] = $val;
        }

        // TODO : Getting All Submission Leave, Sick, Permission, Official Permission
        $whereClause = "DATE(trx_absent_detail.date) BETWEEN '{$prevMonth}' AND '{$nowCutMonth}'
                    AND trx_absent.submissiontype IN ({$mAbsent->Pengajuan_Sakit}, {$mAbsent->Pengajuan_Cuti}, {$mAbsent->Pengajuan_Ijin}, {$mAbsent->Pengajuan_Ijin_Resmi}, {$mAbsent->Pengajuan_Tugas_Kantor})
                    AND trx_absent.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')
                    AND trx_absent_detail.isagree = '{$this->LINESTATUS_Disetujui}'";

        $data = $mAbsentDetail->getAbsentDetail($whereClause)->getResult();
        $allTrxAbsent = [];

        foreach ($data as $val) {
            $allTrxAbsent[$val->md_employee_id][date('Y-m-d', strtotime($val->date))] = $val;
        }

        // TODO : Get All Levelling
        $allLevelling = array_column($mLevel->findAll(), null, 'md_levelling_id');

        // TODO : Get All TKH
        $data = $mAllowance->getAllTotalAmount($prevMonth, $nowCutMonth);
        $allTKH = [];

        foreach ($data as $val) {
            $allTKH[$val->md_employee_id][$val->date] = $val;
        }

        $number = 1;
        $numrow = 5;

        foreach ($sql as $row) {
            $level = $allLevelling[$row->md_levelling_id];
            $empBranch = $mEmpBranch->where('md_employee_id', $row->md_employee_id)->findAll();

            if (count($empBranch) > 1) {
                $branchID = array_column($empBranch, 'md_branch_id');

                $branchID = implode(" ,", $branchID);
            }

            // TODO : Set Data Karyawan
            $sheet->setCellValue('A' . $numrow, $number);
            $sheet->setCellValue('B' . $numrow, $row->nik);
            $sheet->setCellValue('C' . $numrow, $row->fullname);
            $sheet->setCellValue('D' . $numrow, $level ? $level->name : "");

            // TODO : Apply style row yang telah kita buat tadi ke masing-masing baris (isi tabel)
            $sheet->getStyle('A' . $numrow)->applyFromArray($style_row);
            $sheet->getStyle('B' . $numrow)->applyFromArray($style_row);
            $sheet->getStyle('C' . $numrow)->applyFromArray($style_row);
            $sheet->getStyle('D' . $numrow)->applyFromArray($style_row);

            $cell = 'E';
            $total = [];

            foreach ($totalDateRange as $date) {
                $qty = isset($allTKH[$row->md_employee_id][$date]) ? $allTKH[$row->md_employee_id][$date]->tkh : 0;

                //TODO : Get work day employee
                $day = strtoupper(formatDay_idn(date('w', strtotime($date))));

                $whereClause = "md_work_detail.isactive = 'Y'";
                $whereClause .= " AND md_employee_work.md_employee_id = $row->md_employee_id";
                $whereClause .= " AND (md_employee_work.validfrom <= '{$date}' and md_employee_work.validto >= '{$date}')";
                $whereClause .= " AND md_day.name = '$day'";
                $work = $mEmpWork->getEmpWorkDetail($whereClause)->getRow();

                // TODO : Get Assignment Tugas Kantor, Penugasan, Tugas Kantor Setengah Hari
                $assignment = isset($allAssignment[$row->md_employee_id][$date]) ? $allAssignment[$row->md_employee_id][$date] : null;

                // TODO : Get Attendance
                $attendance = isset($allAttendance[$row->md_employee_id][$date]) ? $allAttendance[$row->md_employee_id][$date] : null;

                if ((!$work && !$assignment) || (!$work && ($configMNSOD && $row->md_levelling_id <= $lvlManager) && empty($attendance))) {
                    $styleCell = $style_row_dayoff;
                } else {
                    $trxAbsent = isset($allTrxAbsent[$row->md_employee_id][$date]) ? $allTrxAbsent[$row->md_employee_id][$date] : null;

                    if (!$trxAbsent) {
                        $tugasKunjungan = isset($allTugasKunjungan[$row->md_employee_id][$date]) ? $allTugasKunjungan[$row->md_employee_id][$date] : null;

                        //TODO : Get Attendance if level under Manager and config is nonaktif
                        if ($configMNSOD && $row->md_levelling_id <= $lvlManager) {
                            $clock_in = !empty($attendance->clock_in) ? $attendance->clock_in : null;
                            $clock_out = !empty($attendance->clock_out) ? $attendance->clock_out : null;
                        } else {
                            $whereIn = "v_attendance_branch.md_employee_id = {$row->md_employee_id}";
                            $whereIn .= " AND v_attendance_branch.work_date = '{$date}'";
                            $whereIn .= " AND v_attendance_branch.clock_in != ''";

                            $whereOut = "v_attendance_branch.md_employee_id = {$row->md_employee_id}";
                            $whereOut .= " AND v_attendance_branch.work_date = '{$date}'";
                            $whereOut .= " AND v_attendance_branch.clock_out != ''";

                            if ($tugasKunjungan) {
                                $whereIn .= " AND v_attendance_branch.md_branch_id = {$tugasKunjungan->branch_in_line}";
                                $whereOut .= " AND v_attendance_branch.md_branch_id = {$tugasKunjungan->branch_out_line}";
                            } else {
                                if (!empty($branchID)) {
                                    $whereIn .= " AND v_attendance_branch.md_branch_id IN ($branchID)";
                                    $whereOut .= " AND v_attendance_branch.md_branch_id IN ($branchID)";
                                } else {
                                    $whereIn .= " AND v_attendance_branch.md_branch_id = {$empBranch[0]->md_branch_id}";
                                    $whereOut .= " AND v_attendance_branch.md_branch_id = {$empBranch[0]->md_branch_id}";
                                }
                            }

                            $attIn = $mAttendance->getAttBranch($whereIn, null, true)->getRow();
                            $attOut = $mAttendance->getAttBranch($whereOut, null, true)->getRow();

                            $clock_in = !empty($attIn) ? $attIn->clock_in : null;
                            $clock_out = !empty($attOut) ? $attOut->clock_out : null;
                        }

                        // TODO : Get Submission Forgot Absent In
                        $forgetAbsentIn = isset($allForgetAbsentIn[$row->md_employee_id][$date]) ? $allForgetAbsentIn[$row->md_employee_id][$date] : null;

                        // TODO : Get Submission Forgot Absent Out
                        $forgetAbsentOut = isset($allForgetAbsentOut[$row->md_employee_id][$date]) ? $allForgetAbsentOut[$row->md_employee_id][$date] : null;

                        // TODO : Get Submission Permission Submission Leave Early
                        $leaveEarly = isset($allLeaveEarly[$row->md_employee_id][$date]) ? $allLeaveEarly[$row->md_employee_id][$date] : null;

                        // TODO : Get Submission Half Day Office Duties
                        $officeHalfDay = isset($allOfficeHalfDay[$row->md_employee_id][$date]) ? $allOfficeHalfDay[$row->md_employee_id][$date] : null;

                        if ($officeHalfDay) {
                            $startHour = convertToMinutes(date('H:i', strtotime($officeHalfDay->startdate_realization)));
                            $endHour = convertToMinutes(date('H:i', strtotime($officeHalfDay->enddate_realization)));
                        }

                        // TODO : Getting Submission Leave, Sick, Permission, Official Permission
                        $trxAbsent = isset($allTrxAbsent[$row->md_employee_id][$date]) ? $allTrxAbsent[$row->md_employee_id][$date] : null;

                        // This Variable for calculating if employee absent clock out less than minAbsentOut then meaning employee is late and will be punished for half TKH
                        $breakStart = $work ? convertToMinutes($work->breakstart) : convertToMinutes('12:00');
                        // $minAbsentIn = $work ? convertToMinutes($work->startwork) : convertToMinutes('08:30');
                        $minAbsentOut = $work ? convertToMinutes($work->endwork) : convertToMinutes('15:30');

                        $empClockIn = !empty($clock_in) ? convertToMinutes($clock_in) : null;
                        $empClockOut = !empty($clock_out) ? convertToMinutes($clock_out) : null;

                        if ($work && is_null($empClockIn) && !$forgetAbsentIn && (!$officeHalfDay || ($officeHalfDay && $startHour > $breakStart))) {
                            $qty = 0;
                        }

                        if ($work && is_null($empClockOut) && (!$forgetAbsentOut && !$leaveEarly) && (!$officeHalfDay || ($officeHalfDay && $endHour < $minAbsentOut))) {
                            $qty = 0;
                        }

                        if ($work && !is_null($empClockOut) && $empClockOut < $minAbsentOut && !$leaveEarly && (!$officeHalfDay || ($officeHalfDay && $endHour < $minAbsentOut))) {
                            $qty = 0;
                        }

                        if (!$work && ($tugasKunjungan || ($configMNSOD && $row->md_levelling_id <= $lvlManager)) && $empClockOut < $minAbsentOut) {
                            $qty += -0.5;
                        }
                    }

                    $styleCell = $style_row;
                }

                if (in_array($date, $holiday))
                    $styleCell = $style_row_dayoff;

                $total[] = $qty;
                $sheet->setCellValue($cell . $numrow, $qty);
                $sheet->getStyle($cell . $numrow)->applyFromArray($styleCell);
                $cell++;
            }

            $sheet->setCellValue($cell . $numrow, array_sum($total));
            $sheet->getStyle($cell . $numrow)->applyFromArray($styleCell);
            $sheet->getRowDimension($numrow)->setRowHeight(20);

            $number++;
            $numrow++;
        }

        $sheet->getColumnDimension('A')->setWidth(3); // Set width kolom A
        $sheet->getColumnDimension('B')->setWidth(15); // Set width kolom B
        $sheet->getColumnDimension('C')->setWidth(25); // Set width kolom C
        $sheet->getColumnDimension('D')->setWidth(20); // Set width kolom D
        // Set orientasi kertas jadi LANDSCAPE
        $sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
        // Set judul file excel nya
        $sheet->setTitle("Laporan Absensi Harian");
        // Proses file excel
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Laporan Absensi Harian.xlsx"'); // Set nama file excel nya
        header('Cache-Control: max-age=0');
        $write = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $write->save('php://output');
        exit();
    }

    private function createBorderStyle($bold = false, $center = false, $fillColor = null)
    {
        $style = [
            'font' => ['bold' => $bold],
            'alignment' => [
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ],
            'borders' => array_fill_keys(['top', 'right', 'bottom', 'left'], ['style' => PHPExcel_Style_Border::BORDER_THIN])
        ];

        if ($center) {
            $style['alignment']['horizontal'] = PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        }

        if ($fillColor) {
            $style['fill'] = [
                'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => ['rgb' => $fillColor]
            ];
        }

        return $style;
    }
}

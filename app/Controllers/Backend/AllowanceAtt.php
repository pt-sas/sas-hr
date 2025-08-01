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
use App\Models\M_RuleDetail;
use App\Models\M_WorkDetail;
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
            'month' => date('M-Y')
        ];

        return $this->template->render('report/allowance/v_report_allowance', $data);
    }

    public function reportAll()
    {
        $post = $this->request->getPost();
        $mEmployee = new M_Employee($this->request);
        $mLevel = new M_Levelling($this->request);
        $mAttendance = new M_Attendance($this->request);
        $mAbsent = new M_Absent($this->request);
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);
        $mHoliday = new M_Holiday($this->request);
        $mAbsentDetail = new M_AbsentDetail($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);
        $mEmpBranch = new M_EmpBranch($this->request);

        $md_branch_id = null;
        $md_division_id = null;
        $md_employee_id = null;
        $cutOff = 15;

        $ruleArrive = $mRuleDetail->where(['name' => 'Terlambat 1/2 Hari'])->first();

        if (isset($post['md_branch_id']))
            $md_branch_id = $post['md_branch_id'];

        if (isset($post['md_division_id']))
            $md_division_id = $post['md_division_id'];

        if (isset($post['md_employee_id']) && $post['md_employee_id'])
            $md_employee_id = $post['md_employee_id'];

        $periode = $post['periode'];

        // Panggil class PHPExcel nya
        $excel = new PHPExcel();
        // Settingan awal file excel
        $excel->getProperties()->setCreator('Laporan Saldo TKH')
            ->setLastModifiedBy('Laporan Saldo TKH')
            ->setTitle("Laporan Saldo TKH")
            ->setSubject("Laporan Saldo TKH")
            ->setDescription("Laporan Saldo TKH")
            ->setKeywords("Laporan Saldo TKH");
        // Buat sebuah variabel untuk menampung pengaturan style dari header tabel
        $style_col = array(
            'font' => array('bold' => true), // Set font nya jadi bold
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, // Set text jadi ditengah secara horizontal (center)
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER // Set text jadi di tengah secara vertical (middle)
            ),
            'borders' => array(
                'top' => array('style'  => PHPExcel_Style_Border::BORDER_THIN), // Set border top dengan garis tipis
                'right' => array('style'  => PHPExcel_Style_Border::BORDER_THIN),  // Set border right dengan garis tipis
                'bottom' => array('style'  => PHPExcel_Style_Border::BORDER_THIN), // Set border bottom dengan garis tipis
                'left' => array('style'  => PHPExcel_Style_Border::BORDER_THIN) // Set border left dengan garis tipis
            )
        );
        // Buat sebuah variabel untuk menampung pengaturan style dari isi tabel
        $style_row = array(
            'alignment' => array(
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER // Set text jadi di tengah secara vertical (middle)
            ),
            'borders' => array(
                'top' => array('style'  => PHPExcel_Style_Border::BORDER_THIN), // Set border top dengan garis tipis
                'right' => array('style'  => PHPExcel_Style_Border::BORDER_THIN),  // Set border right dengan garis tipis
                'bottom' => array('style'  => PHPExcel_Style_Border::BORDER_THIN), // Set border bottom dengan garis tipis
                'left' => array('style'  => PHPExcel_Style_Border::BORDER_THIN) // Set border left dengan garis tipis
            )
        );
        // Buat sebuah variabel untuk menampung pengaturan style dari isi tabel
        $style_row_dayoff = [
            'alignment' => array(
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER // Set text jadi di tengah secara vertical (middle)
            ),
            'borders' => array(
                'top' => array('style'  => PHPExcel_Style_Border::BORDER_THIN), // Set border top dengan garis tipis
                'right' => array('style'  => PHPExcel_Style_Border::BORDER_THIN),  // Set border right dengan garis tipis
                'bottom' => array('style'  => PHPExcel_Style_Border::BORDER_THIN), // Set border bottom dengan garis tipis
                'left' => array('style'  => PHPExcel_Style_Border::BORDER_THIN) // Set border left dengan garis tipis
            ),
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => 'FF0000')
            )
        ];
        $excel->setActiveSheetIndex(0)->setCellValue('A1', "LAPORAN ABSENSI HARIAN"); // Set kolom A1 dengan tulisan "LAPORAN ABSENSI HARIAN"
        $excel->getActiveSheet()->getStyle('A1')->getFont()->setBold(TRUE); // Set bold kolom A1
        $excel->getActiveSheet()->getStyle('A1')->getFont()->setSize(15); // Set font size 15 untuk kolom A1
        $excel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER); // Set text center untuk kolom A1
        // Buat header tabel nya pada baris ke 3
        $excel->setActiveSheetIndex(0)->setCellValue('A3', "No"); // Set kolom A3 dengan tulisan "NO"
        $excel->getActiveSheet()->mergeCells('A3:A4'); // Set Merge Cell
        $excel->setActiveSheetIndex(0)->setCellValue('B3', "NIK"); // Set kolom B3 dengan tulisan "Kode"
        $excel->getActiveSheet()->mergeCells('B3:B4'); // Set Merge Cell
        $excel->setActiveSheetIndex(0)->setCellValue('C3', "Nama"); // Set kolom C3 dengan tulisan "NAMA"
        $excel->getActiveSheet()->mergeCells('C3:C4'); // Set Merge Cell
        $excel->setActiveSheetIndex(0)->setCellValue('D3', "Jabatan"); // Set kolom D3 dengan tulisan "Jabatan"
        $excel->getActiveSheet()->mergeCells('D3:D4'); // Set Merge Cell

        $cell = 'E';
        $cellRow = 4;

        $firstCell = '';
        $lastCell = '';

        $prevDayMonth = $cutOff + 1;
        $prevMonth = date('Y-m-' . $prevDayMonth, strtotime($periode . '-1 month'));
        $prevLastDay = new DateTime($prevMonth);
        $prevLastDay = $prevLastDay->format('Y-m-t');
        $prevMonthYear = date('M-Y', strtotime($prevMonth));
        $prevDateRange = getDatesFromRange($prevMonth, $prevLastDay, [], 'Y-m-d', 'all');

        $excel->setActiveSheetIndex(0)->setCellValue('E3', $prevMonthYear); // Set kolom E3 dengan tulisan "Bulan"

        foreach ($prevDateRange as $date) {
            $date = date('d', strtotime($date));

            $excel->setActiveSheetIndex(0)->setCellValue($cell . $cellRow, $date);
            $excel->getActiveSheet()->getStyle($cell . $cellRow)->applyFromArray($style_col);

            if (count($prevDateRange) - 1)
                $lastCell = $cell;

            $cell++;
        }

        $excel->getActiveSheet()->mergeCells('E3:' . $lastCell . '3'); // Set Merge Cell
        $excel->getActiveSheet()->getStyle('E3:' . $lastCell . '3')->applyFromArray($style_col);

        $day = $cutOff - 1;
        $periode = date('Y-m-d', strtotime($periode));
        $lastCutDay = date('Y-m-d', strtotime($periode . '+' . $day . 'day'));
        $monthYear = date('M-Y', strtotime($periode));
        $dateRange = getDatesFromRange($periode, $lastCutDay, [], 'Y-m-d', 'all');

        $firstCell = $cell;
        $excel->setActiveSheetIndex(0)->setCellValue($firstCell . '3', $monthYear); // Set kolom F3 dengan tulisan "Bulan Next"

        foreach ($dateRange as $date) {
            $date = date('d', strtotime($date));

            $excel->setActiveSheetIndex(0)->setCellValue($cell . $cellRow, $date);
            $excel->getActiveSheet()->getStyle($cell . $cellRow)->applyFromArray($style_col);

            if (count($dateRange) - 1)
                $lastCell = $cell;

            $cell++;
        }

        $excel->getActiveSheet()->mergeCells($firstCell . '3:' . $lastCell . '3'); // Set Merge Cell
        $excel->getActiveSheet()->getStyle($firstCell . '3:' . $lastCell . '3')->applyFromArray($style_col);

        // $excel->getActiveSheet()->mergeCells($cell . '3:' . $cell . '4'); // Set Merge Cell
        // $excel->setActiveSheetIndex(0)->setCellValue('D3', "Jabatan"); // Set kolom D3 dengan tulisan "Jabatan"
        $excel->setActiveSheetIndex(0)->setCellValue($cell . '3', "Total"); // Set kolom E3 dengan tulisan "Bulan"
        $excel->getActiveSheet()->mergeCells($cell . '3:' . $cell . '4')->getStyle($cell . '3:' . $cell . '4')->applyFromArray($style_col); // Set Merge Cell
        // $excel->setActiveSheetIndex(0)->setCellValue($cell . '3', $cell); // Set kolom E3 dengan tulisan "Bulan"
        // $excel->getActiveSheet()->getStyle($cell . '3:' . $cell . '4')->applyFromArray($style_col);

        $excel->getActiveSheet()->mergeCells('A1:' . $cell . '1'); // Set Merge Cell pada kolom A1 sampai F1

        // Apply style header yang telah kita buat tadi ke masing-masing kolom header
        $excel->getActiveSheet()->getStyle('A3:A4')->applyFromArray($style_col);
        $excel->getActiveSheet()->getStyle('B3:B4')->applyFromArray($style_col);
        $excel->getActiveSheet()->getStyle('C3:C4')->applyFromArray($style_col);
        $excel->getActiveSheet()->getStyle('D3:D4')->applyFromArray($style_col);

        if ($md_employee_id) {
            $sql = $mEmployee->where([
                'isactive'          => 'Y',
                'md_status_id <> '  => 100004
            ])->whereIn('md_employee_id', $md_employee_id)
                ->orderBy('fullname', 'ASC')->findAll();
        } else {
            $sql = $mEmployee->where([
                'isactive'          => 'Y',
                'md_status_id <> '  => 100004
            ])->orderBy('fullname', 'ASC')->findAll();
        }

        $no = 1; // Untuk penomoran tabel, di awal set dengan 1
        $numrow = 5; // Set baris pertama untuk isi tabel adalah baris ke 4

        // while ($data = $sql->fetch()) { // Ambil semua data dari hasil eksekusi $sql
        foreach ($sql as $row) {
            $level = $mLevel->find($row->md_levelling_id);

            $excel->setActiveSheetIndex(0)->setCellValue('A' . $numrow, $no);
            $excel->setActiveSheetIndex(0)->setCellValue('B' . $numrow, $row->nik);
            $excel->setActiveSheetIndex(0)->setCellValue('C' . $numrow, $row->fullname);
            $excel->setActiveSheetIndex(0)->setCellValue('D' . $numrow, $level ? $level->name : "");

            // Apply style row yang telah kita buat tadi ke masing-masing baris (isi tabel)
            $excel->getActiveSheet()->getStyle('A' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('B' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('C' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('D' . $numrow)->applyFromArray($style_row);

            $holiday = $mHoliday->getHolidayDate();

            $cell = 'E';
            $prevTotal = [];
            foreach ($prevDateRange as $date) {
                $qty = 1;

                $day = date('w', strtotime($date));

                //TODO : Get work day employee
                $workDay = $mEmpWork->where([
                    'md_employee_id'    => $row->md_employee_id,
                    'validfrom <='      => $date,
                    'validto >='        => $date
                ])->orderBy('validfrom', 'ASC')->first();

                if (is_null($workDay)) {
                    $qty = 0;
                    $styleCell = $style_row_dayoff;
                } else {
                    $day = strtoupper(formatDay_idn($day));

                    //TODO: Get Work Detail by day 
                    $work = null;

                    $whereClause = "md_work_detail.isactive = 'Y'";
                    $whereClause .= " AND md_employee_work.md_employee_id = $row->md_employee_id";
                    $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
                    $whereClause .= " AND md_day.name = '$day'";
                    $work = $mWorkDetail->getWorkDetail($whereClause)->getRow();

                    $parAbsent = "DATE_FORMAT(v_realization.date, '%Y-%m-%d') = '{$date}'
                        AND v_realization.md_employee_id = {$row->md_employee_id}
                        AND v_realization.isagree = 'Y'
                        AND v_realization.submissiontype IN ({$mAbsent->Pengajuan_Penugasan}, {$mAbsent->Pengajuan_Tugas_Kantor})";

                    $assignment = $mAbsentDetail->getAllSubmission($parAbsent)->getRow();

                    if (is_null($work) && is_null($assignment)) {
                        $qty = 0;
                        $styleCell = $style_row_dayoff;
                    } else {
                        // TODO : Get Allowance
                        $parAllow = [
                            'trx_allow_attendance.md_employee_id'                           => $row->md_employee_id,
                            'DATE_FORMAT(trx_allow_attendance.submissiondate, "%Y-%m-%d")'  => $date
                        ];
                        $allow = $this->model->getAllowance($parAllow)->getRow();

                        //TODO : Get Attendance 
                        $empBranch = $mEmpBranch->where([$mEmployee->primaryKey => $row->md_employee_id])->findAll();

                        $whereClause = "v_attendance_serialnumber.md_employee_id = {$row->md_employee_id}";
                        $whereClause .= " AND v_attendance_serialnumber.date = '{$date}'";

                        if (count($empBranch) > 1) {
                            $branchID = [];

                            foreach ($empBranch as $val) {
                                $branchID[] = $val->md_branch_id;
                            }

                            $branchID = implode(" ,", $branchID);
                            $whereClause .= " AND md_attendance_machines.md_branch_id IN ($branchID)";
                        } else {
                            $whereClause .= " AND md_attendance_machines.md_branch_id = {$empBranch[0]->md_branch_id}";
                        }

                        $attend = $mAttendance->getAttendanceBranch($whereClause)->getRow();

                        //TODO : Get Submission  
                        $parAbsent = "DATE_FORMAT(v_realization.date, '%Y-%m-%d') = '{$date}'
                        AND v_realization.md_employee_id = {$row->md_employee_id}
                        AND v_realization.isagree = 'Y'
                        AND v_realization.submissiontype NOT IN ({$mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari})";

                        $absent = $mAbsentDetail->getAllSubmission($parAbsent)->getRow();

                        // TODO : Get Submission Permission Arrived and Forgot Absent In
                        $parAbsent = "DATE_FORMAT(v_realization.date, '%Y-%m-%d') = '{$date}'
                        AND v_realization.md_employee_id = {$row->md_employee_id}
                        AND v_realization.isagree = 'Y'
                        AND v_realization.submissiontype IN ({$mAbsent->Pengajuan_Datang_Terlambat}, {$mAbsent->Pengajuan_Lupa_Absen_Masuk})";

                        $absentIn = $mAbsentDetail->getAllSubmission($parAbsent)->getRow();

                        // TODO : Get Submission Permission Leave Early and Forgot Absent Out
                        $parAbsent = "DATE_FORMAT(v_realization.date, '%Y-%m-%d') = '{$date}'
                        AND v_realization.md_employee_id = {$row->md_employee_id}
                        AND v_realization.isagree = 'Y'
                        AND v_realization.submissiontype IN ({$mAbsent->Pengajuan_Lupa_Absen_Pulang}, {$mAbsent->Pengajuan_Pulang_Cepat})";

                        $absentOut = $mAbsentDetail->getAllSubmission($parAbsent)->getRow();

                        // TODO : Get Submission Permission Leave Early and Not Approved
                        $parAbsent = "DATE_FORMAT(trx_absent.startdate, '%Y-%m-%d') = '{$date}'
                        AND trx_absent.docstatus = 'CO'
                        AND trx_absent_detail.isagree = 'N'
                        AND trx_absent.md_employee_id = {$row->md_employee_id}
                        AND trx_absent.submissiontype IN ({$mAbsent->Pengajuan_Pulang_Cepat})";

                        $absentNA = $mAbsentDetail->getAbsentDetail($parAbsent)->getRow();

                        // TODO : Get Submission Forgot Absent Out and Not Approved
                        $parAbsent = "DATE_FORMAT(trx_absent.startdate, '%Y-%m-%d') = '{$date}'
                        AND trx_absent.docstatus = 'CO'
                        AND trx_absent.md_employee_id = {$row->md_employee_id}
                        AND trx_absent_detail.isagree = 'N'
                        AND trx_absent.submissiontype IN ({$mAbsent->Pengajuan_Lupa_Absen_Pulang})";

                        $forgetAbsentNA = $mAbsentDetail->getAbsentDetail($parAbsent)->getRow();

                        // TODO : Get Submission Half Day Assignment
                        $parAbsent = "DATE_FORMAT(trx_absent_detail.date, '%Y-%m-%d') = '{$date}'
                            AND trx_absent.docstatus = 'CO'
                            AND trx_absent.md_employee_id = {$row->md_employee_id}
                            AND trx_absent_detail.isagree = 'Y'
                            AND trx_absent.submissiontype IN ({$mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari})
                            AND trx_absent.isbranch = 'N'";

                        $tugasNotKunjungan = $mAbsentDetail->getAbsentDetail($parAbsent)->getRow();

                        $qty = 1;

                        if ($attend) {
                            // This Variable for calculating if employee absent clock in greater than maxAbsentIn then meaning employee is late and will be punished for half TKH
                            $maxAbsentIn = convertToMinutes($work->startwork) + $ruleArrive->condition;
                            // This Variable for calculating if employee absent clock out less than minAbsentOut then meaning employee is late and will be punished for half TKH
                            $minAbsentOut = convertToMinutes($work->endwork);

                            $empClockIn = !empty($attend->clock_in) ? convertToMinutes($attend->clock_in) : null;
                            $empClockOut = !empty($attend->clock_out) ? convertToMinutes($attend->clock_out) : null;

                            if (
                                empty($tugasNotKunjungan) && (!is_null($empClockIn) && $empClockIn > $maxAbsentIn)
                                || (!is_null($empClockOut) && $empClockOut < $minAbsentOut)
                            ) {
                                if ($absent && $allow && ($absent->submissiontype == $mAbsent->Pengajuan_Datang_Terlambat
                                    || $absent->submissiontype == $mAbsent->Pengajuan_Pulang_Cepat || $absent->submissiontype == $mAbsent->Pengajuan_Penugasan)) {
                                    $qty = $qty + $allow->amount;
                                } else if (!empty($absentNA)) {
                                    $qty = $qty + -1.5;
                                } else {
                                    $qty = 0;
                                }
                            }

                            if (empty($tugasNotKunjungan) && is_null($empClockIn) && $empClockOut >= $minAbsentOut) {
                                if (
                                    $absent
                                    && $absent->enddate_realization !== "0000-00-00 00:00:00"
                                    && convertToMinutes($absent->enddate_realization) < convertToMinutes($work->breakstart)
                                    && $absent->submissiontype == $mAbsent->Pengajuan_Lupa_Absen_Masuk
                                ) {
                                    $qty = $qty - 0.5;
                                } else if (empty($absent)) {
                                    $qty = 0;
                                }
                            }

                            if (empty($tugasNotKunjungan) && is_null($empClockOut) && $empClockIn <= $maxAbsentIn) {
                                if (
                                    $absent
                                    && $absent->enddate_realization !== "0000-00-00 00:00:00"
                                    && convertToMinutes($absent->enddate_realization) > convertToMinutes($work->breakend)
                                    && $absent->submissiontype == $mAbsent->Pengajuan_Lupa_Absen_Pulang
                                ) {
                                    $qty = $qty - 0.5;
                                } else if (!empty($absentNA)) {
                                    if (!empty($forgetAbsentNA)) {
                                        $qty = $qty + -1.5;
                                    } else {
                                        $qty = $qty + -2;
                                    }
                                } else if (empty($absent)) {
                                    $qty = 0;
                                }
                            }

                            if ($absent && empty($tugasNotKunjungan) && $absent->submissiontype == $mAbsent->Pengajuan_Penugasan) {
                                if ($allow) {
                                    $qty = $qty + $allow->amount;
                                }
                            }

                            if ($absentIn && $absentOut) {
                                $qty = 0;
                            }
                        } else if (empty($attend)) {
                            if ($absent && $allow && $allow->amount < 0) {
                                $qty += $allow->amount;
                            } else if ($absent && $allow && $allow->amount > 0) {
                                $qty = $allow->amount;
                            } else {
                                $qty = 0;
                            }
                        } else {
                            $qty = 0;
                        }

                        $styleCell = $style_row;
                    }
                }

                if (in_array($date, $holiday))
                    $styleCell = $style_row_dayoff;

                $value = $qty;
                $prevTotal[] = $value;
                $excel->setActiveSheetIndex(0)->setCellValue($cell . $numrow, $value);
                $excel->getActiveSheet()->getStyle($cell . $numrow)->applyFromArray($styleCell);
                $cell++;
            }

            foreach ($dateRange as $date) {
                $qty = 1;

                $day = date('w', strtotime($date));

                //TODO : Get work day employee
                $workDay = $mEmpWork->where([
                    'md_employee_id'    => $row->md_employee_id,
                    'validfrom <='      => $date,
                    'validto >='        => $date
                ])->orderBy('validfrom', 'ASC')->first();

                if (is_null($workDay)) {
                    $qty = 0;
                    $styleCell = $style_row_dayoff;
                } else {
                    $day = strtoupper(formatDay_idn($day));

                    //TODO: Get Work Detail by day 
                    $work = null;

                    $whereClause = "md_work_detail.isactive = 'Y'";
                    $whereClause .= " AND md_employee_work.md_employee_id = $row->md_employee_id";
                    $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
                    $whereClause .= " AND md_day.name = '$day'";
                    $work = $mWorkDetail->getWorkDetail($whereClause)->getRow();

                    $parAbsent = "DATE_FORMAT(v_realization.date, '%Y-%m-%d') = '{$date}'
                        AND v_realization.md_employee_id = {$row->md_employee_id}
                        AND v_realization.isagree = 'Y'
                        AND v_realization.submissiontype IN ({$mAbsent->Pengajuan_Penugasan}, {$mAbsent->Pengajuan_Tugas_Kantor})";

                    $assignment = $mAbsentDetail->getAllSubmission($parAbsent)->getRow();

                    if (is_null($work) && is_null($assignment)) {
                        $qty = 0;
                        $styleCell = $style_row_dayoff;
                    } else {
                        // TODO : Get Allowance
                        $parAllow = [
                            'trx_allow_attendance.md_employee_id'                           => $row->md_employee_id,
                            'DATE_FORMAT(trx_allow_attendance.submissiondate, "%Y-%m-%d")'  => $date
                        ];
                        $allow = $this->model->getAllowance($parAllow)->getRow();

                        //TODO : Get Attendance 
                        $empBranch = $mEmpBranch->where([$mEmployee->primaryKey => $row->md_employee_id])->findAll();

                        $whereClause = "v_attendance_serialnumber.md_employee_id = {$row->md_employee_id}";
                        $whereClause .= " AND v_attendance_serialnumber.date = '{$date}'";

                        if (count($empBranch) > 1) {
                            $branchID = [];

                            foreach ($empBranch as $val) {
                                $branchID[] = $val->md_branch_id;
                            }

                            $branchID = implode(" ,", $branchID);
                            $whereClause .= " AND md_attendance_machines.md_branch_id IN ($branchID)";
                        } else {
                            $whereClause .= " AND md_attendance_machines.md_branch_id = {$empBranch[0]->md_branch_id}";
                        }

                        $attend = $mAttendance->getAttendanceBranch($whereClause)->getRow();

                        //TODO : Get Submission  
                        $parAbsent = "DATE_FORMAT(v_realization.date, '%Y-%m-%d') = '{$date}'
                        AND v_realization.md_employee_id = {$row->md_employee_id}
                        AND v_realization.isagree = 'Y'
                        AND v_realization.submissiontype NOT IN ({$mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari})";

                        $absent = $mAbsentDetail->getAllSubmission($parAbsent)->getRow();

                        // TODO : Get Submission Permission Arrived and Forgot Absent In
                        $parAbsent = "DATE_FORMAT(v_realization.date, '%Y-%m-%d') = '{$date}'
                        AND v_realization.md_employee_id = {$row->md_employee_id}
                        AND v_realization.isagree = 'Y'
                        AND v_realization.submissiontype IN ({$mAbsent->Pengajuan_Datang_Terlambat}, {$mAbsent->Pengajuan_Lupa_Absen_Masuk})";

                        $absentIn = $mAbsentDetail->getAllSubmission($parAbsent)->getRow();

                        // TODO : Get Submission Permission Leave Early and Forgot Absent Out
                        $parAbsent = "DATE_FORMAT(v_realization.date, '%Y-%m-%d') = '{$date}'
                        AND v_realization.md_employee_id = {$row->md_employee_id}
                        AND v_realization.isagree = 'Y'
                        AND v_realization.submissiontype IN ({$mAbsent->Pengajuan_Lupa_Absen_Pulang}, {$mAbsent->Pengajuan_Pulang_Cepat})";

                        $absentOut = $mAbsentDetail->getAllSubmission($parAbsent)->getRow();

                        // TODO : Get Submission Permission Leave Early and Not Approved
                        $parAbsent = "DATE_FORMAT(trx_absent.startdate, '%Y-%m-%d') = '{$date}'
                        AND trx_absent.docstatus = 'NA'
                        AND trx_absent_detail.isagree = 'N'
                        AND trx_absent.md_employee_id = {$row->md_employee_id}
                        AND trx_absent.submissiontype IN ({$mAbsent->Pengajuan_Pulang_Cepat})";

                        $absentNA = $mAbsentDetail->getAbsentDetail($parAbsent)->getRow();

                        // TODO : Get Submission Forgot Absent Out and Not Approved
                        $parAbsent = "DATE_FORMAT(trx_absent.startdate, '%Y-%m-%d') = '{$date}'
                        AND trx_absent.docstatus = 'CO'
                        AND trx_absent.md_employee_id = {$row->md_employee_id}
                        AND trx_absent_detail.isagree = 'N'
                        AND trx_absent.submissiontype IN ({$mAbsent->Pengajuan_Lupa_Absen_Pulang})";

                        $forgetAbsentNA = $mAbsentDetail->getAbsentDetail($parAbsent)->getRow();

                        // TODO : Get Submission Half Day Assignment
                        $parAbsent = "DATE_FORMAT(trx_absent_detail.date, '%Y-%m-%d') = '{$date}'
                            AND trx_absent.docstatus = 'CO'
                            AND trx_absent.md_employee_id = {$row->md_employee_id}
                            AND trx_absent_detail.isagree = 'Y'
                            AND trx_absent.submissiontype IN ({$mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari})
                            AND trx_absent.isbranch = 'N'";

                        $tugasNotKunjungan = $mAbsentDetail->getAbsentDetail($parAbsent)->getRow();

                        $qty = 1;

                        if ($attend) {
                            // This Variable for calculating if employee absent clock in greater than maxAbsentIn then meaning employee is late and will be punished for half TKH
                            $maxAbsentIn = convertToMinutes($work->startwork) + $ruleArrive->condition;
                            // This Variable for calculating if employee absent clock out less than minAbsentOut then meaning employee is late and will be punished for half TKH
                            $minAbsentOut = convertToMinutes($work->endwork);

                            $empClockIn = !empty($attend->clock_in) ? convertToMinutes($attend->clock_in) : null;
                            $empClockOut = !empty($attend->clock_out) ? convertToMinutes($attend->clock_out) : null;

                            if (
                                empty($tugasNotKunjungan) && (!is_null($empClockIn) && $empClockIn > $maxAbsentIn)
                                || (!is_null($empClockOut) && $empClockOut < $minAbsentOut)
                            ) {
                                if ($absent && $allow && ($absent->submissiontype == $mAbsent->Pengajuan_Pulang_Cepat || $absent->submissiontype == $mAbsent->Pengajuan_Penugasan)) {
                                    $qty = $qty + $allow->amount;
                                } else if (!empty($absentNA)) {
                                    $qty = $qty + -1.5;
                                } else {
                                    $qty = 0;
                                }
                            }

                            if (empty($tugasNotKunjungan) && is_null($empClockIn) && $empClockOut >= $minAbsentOut) {
                                if (
                                    $absent
                                    && $absent->enddate_realization !== "0000-00-00 00:00:00"
                                    && convertToMinutes($absent->enddate_realization) < convertToMinutes($work->breakstart)
                                    && $absent->submissiontype == $mAbsent->Pengajuan_Lupa_Absen_Masuk
                                ) {
                                    $qty = $qty - 0.5;
                                } else if (!empty($absentNA)) {
                                    if (!empty($forgetAbsentNA)) {
                                        $qty = $qty + -1.5;
                                    } else {
                                        $qty = $qty + -2;
                                    }
                                } else if (empty($absent)) {
                                    $qty = 0;
                                }
                            }

                            if (empty($tugasNotKunjungan) && is_null($empClockOut) && $empClockIn <= $maxAbsentIn) {
                                if (
                                    $absent
                                    && $absent->enddate_realization !== "0000-00-00 00:00:00"
                                    && convertToMinutes($absent->enddate_realization) > convertToMinutes($work->breakend)
                                    && $absent->submissiontype == $mAbsent->Pengajuan_Lupa_Absen_Pulang
                                ) {
                                    $qty = $qty - 0.5;
                                } else if (empty($absent)) {
                                    $qty = 0;
                                }
                            }

                            if ($absent && empty($tugasNotKunjungan) && $absent->submissiontype == $mAbsent->Pengajuan_Penugasan) {
                                if ($allow) {
                                    $qty = $qty + $allow->amount;
                                }
                            }

                            if ($absentIn && $absentOut) {
                                $qty = 0;
                            }
                        } else if (empty($attend)) {
                            if ($absent && $allow && $allow->amount < 0) {
                                $qty += $allow->amount;
                            } else if ($absent && $allow && $allow->amount > 0) {
                                $qty = $allow->amount;
                            } else {
                                $qty = 0;
                            }
                        } else {
                            $qty = 0;
                        }

                        $styleCell = $style_row;
                    }
                }

                if (in_array($date, $holiday))
                    $styleCell = $style_row_dayoff;

                $value = $qty;
                $prevTotal[] = $value;
                $excel->setActiveSheetIndex(0)->setCellValue($cell . $numrow, $value);
                $excel->getActiveSheet()->getStyle($cell . $numrow)->applyFromArray($styleCell);
                $cell++;
            }

            $excel->setActiveSheetIndex(0)->setCellValue($cell . $numrow, array_sum($prevTotal));
            $excel->getActiveSheet()->getStyle($cell . $numrow)->applyFromArray($styleCell);
            $excel->getActiveSheet()->getRowDimension($numrow)->setRowHeight(20);

            $no++; // Tambah 1 setiap kali looping
            $numrow++; // Tambah 1 setiap kali looping
        }
        // Set width kolom
        $excel->getActiveSheet()->getColumnDimension('A')->setWidth(3); // Set width kolom A
        $excel->getActiveSheet()->getColumnDimension('B')->setWidth(15); // Set width kolom B
        $excel->getActiveSheet()->getColumnDimension('C')->setWidth(25); // Set width kolom C
        $excel->getActiveSheet()->getColumnDimension('D')->setWidth(20); // Set width kolom D
        // $excel->getActiveSheet()->getColumnDimension($cell)->setWidth(20); // Set width kolom D
        // $excel->getActiveSheet()->getColumnDimension('E')->setWidth(15); // Set width kolom E
        // $excel->getActiveSheet()->getColumnDimension('F')->setWidth(30); // Set width kolom F
        // Set orientasi kertas jadi LANDSCAPE
        $excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
        // Set judul file excel nya
        $excel->getActiveSheet(0)->setTitle("Laporan Absensi Harian");
        $excel->setActiveSheetIndex(0);
        // Proses file excel
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Laporan Absensi Harian.xlsx"'); // Set nama file excel nya
        header('Cache-Control: max-age=0');
        $write = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $write->save('php://output');
        exit();
        // return json_encode($dateRange);
    }

    public function reportAllNew()
    {
        $post = $this->request->getPost();

        $mEmployee = new M_Employee($this->request);
        $mLevel = new M_Levelling($this->request);
        $mAttendance = new M_Attendance($this->request);
        $mAbsent = new M_Absent($this->request);
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

        if (isset($post['md_employee_id']) && $post['md_employee_id'])
            $md_employee_id = $post['md_employee_id'];

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
        $builder = $mEmployee->where([
            'isactive'         => 'Y',
            'md_status_id <>'  => 100004
        ]);

        if ($md_employee_id) {
            $builder = $builder->whereIn('md_employee_id', $md_employee_id);
        }

        $sql = $builder->orderBy('fullname', 'ASC')->findAll();
        $holiday = $mHoliday->getHolidayDate();
        $totalDateRange = getDatesFromRange($prevMonth, $nowCutMonth, [], 'Y-m-d', 'all');

        $number = 1;
        $numrow = 5;

        foreach ($sql as $row) {
            $level = $mLevel->find($row->md_levelling_id);

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
                $qty = $mAllowance->getTotalAmount($row->md_employee_id, $date);

                //TODO : Get work day employee
                $day = strtoupper(formatDay_idn(date('w', strtotime($date))));

                $whereClause = "md_work_detail.isactive = 'Y'";
                $whereClause .= " AND md_employee_work.md_employee_id = $row->md_employee_id";
                $whereClause .= " AND (md_employee_work.validfrom <= '{$date}' and md_employee_work.validto >= '{$date}')";
                $whereClause .= " AND md_day.name = '$day'";
                $work = $mEmpWork->getEmpWorkDetail($whereClause)->getRow();

                // TODO : Get Assignment
                $whereClause = "DATE(v_all_submission.date) = '{$date}'";
                $whereClause .= " AND v_all_submission.md_employee_id = {$row->md_employee_id}";
                $whereClause .= " AND v_all_submission.isagree = 'Y'";
                $whereClause .= " AND v_all_submission.submissiontype IN (100007, 100008)";

                $assignment = $mAbsent->getAllSubmission($whereClause)->getRow();

                if (!$work && !$assignment) {
                    $styleCell = $style_row_dayoff;
                } else {
                    //TODO : Get Attendance 
                    $empBranch = $mEmpBranch->where([$mEmployee->primaryKey => $row->md_employee_id])->findAll();

                    $whereClause = "v_attendance_serialnumber.md_employee_id = {$row->md_employee_id}";
                    $whereClause .= " AND v_attendance_serialnumber.date = '{$date}'";

                    if (count($empBranch) > 1) {
                        $branchID = array_column($empBranch, 'md_branch_id');

                        $branchID = implode(" ,", $branchID);
                        $whereClause .= " AND md_attendance_machines.md_branch_id IN ($branchID)";
                    } else {
                        $whereClause .= " AND md_attendance_machines.md_branch_id = {$empBranch[0]->md_branch_id}";
                    }

                    $attendance = $mAttendance->getAttendanceBranch($whereClause)->getRow();

                    // TODO : Get Submission Forgot Absent In
                    $whereClause = "DATE(v_realization.date) = '{$date}'
                        AND v_realization.md_employee_id = {$row->md_employee_id}
                        AND v_realization.isagree = 'Y'
                        AND v_realization.submissiontype IN ({$mAbsent->Pengajuan_Lupa_Absen_Masuk})";

                    $forgetAbsentIn = $mAbsentDetail->getAllSubmission($whereClause)->getRow();

                    // TODO : Get Submission Forgot Absent Out
                    $whereClause = "DATE(v_realization.date) = '{$date}'
                        AND v_realization.md_employee_id = {$row->md_employee_id}
                        AND v_realization.isagree = 'Y'
                        AND v_realization.submissiontype IN ({$mAbsent->Pengajuan_Lupa_Absen_Pulang})";

                    $forgetAbsentOut = $mAbsentDetail->getAllSubmission($whereClause)->getRow();

                    // TODO : Get Submission Permission Submission Leave
                    $whereClause = "DATE(v_realization.date) = '{$date}'
                        AND v_realization.md_employee_id = {$row->md_employee_id}
                        AND v_realization.isagree = 'Y'
                        AND v_realization.submissiontype IN ({$mAbsent->Pengajuan_Pulang_Cepat})";

                    $absentOut = $mAbsentDetail->getAllSubmission($whereClause)->getRow();

                    // TODO : Get Submission Half Day Assignment
                    $whereClause = "DATE(trx_absent_detail.date) = '{$date}'
                    AND trx_absent.docstatus = 'CO'
                    AND trx_absent.md_employee_id = {$row->md_employee_id}
                    AND trx_absent_detail.isagree = 'Y'
                    AND trx_absent.submissiontype IN ({$mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari})";

                    $tugasNotKunjungan = $mAbsentDetail->getAbsentDetail($whereClause)->getRow();

                    // TODO : Getting Submission Leave, Sick, Permission, Official Permission
                    $whereClause = "trx_absent.md_employee_id = {$row->md_employee_id}";
                    $whereClause .= " AND DATE(trx_absent_detail.date) = '{$date}'";
                    $whereClause .= " AND trx_absent.submissiontype IN (100004, 100001, 100003, 100005)";
                    $whereClause .= " AND trx_absent.docstatus IN ('CO', 'IP')";
                    $whereClause .= " AND trx_absent_detail.isagree IN ('Y', 'M', 'S')";
                    $trxAbsent = $mAbsentDetail->getAbsentDetail($whereClause)->getRow();

                    if ($attendance && !$assignment && !$trxAbsent) {
                        // TODO : This for Normal Attendance

                        // This Variable for calculating if employee absent clock out less than minAbsentOut then meaning employee is late and will be punished for half TKH
                        $minAbsentOut = convertToMinutes($work->endwork);

                        $empClockIn = !empty($attendance->clock_in) ? convertToMinutes($attendance->clock_in) : null;
                        $empClockOut = !empty($attendance->clock_out) ? convertToMinutes($attendance->clock_out) : null;

                        if (is_null($empClockIn) && !$forgetAbsentIn && !$tugasNotKunjungan) {
                            $qty = 0;
                        }

                        if (is_null($empClockOut) && !$forgetAbsentOut && !$tugasNotKunjungan) {
                            $qty = 0;
                        }

                        if ($empClockOut < $minAbsentOut && !$absentOut) {
                            $qty = 0;
                        }
                    } else if ($assignment && $assignment->submissiontype === 100008 && !$trxAbsent) {
                        // TODO : This for Assignment Attendance
                        $assignTrx = $mAssignmentDate->where('trx_assignment_date_id', $assignment->id)->first();

                        $whereClause = "v_attendance_serialnumber.md_employee_id = {$row->md_employee_id}";
                        $whereClause .= " AND v_attendance_serialnumber.date = '{$date}'";
                        $whereClause .= " AND md_attendance_machines.md_branch_id = {$assignTrx->branch_in}";
                        $attendBranchin = $mAttendance->getAttendanceBranch($whereClause)->getRow();

                        $whereClause = "v_attendance_serialnumber.md_employee_id = {$row->md_employee_id}";
                        $whereClause .= " AND v_attendance_serialnumber.date = '{$date}'";
                        $whereClause .= " AND md_attendance_machines.md_branch_id = {$assignTrx->branch_out}";
                        $attendBranchOut = $mAttendance->getAttendanceBranch($whereClause)->getRow();

                        // This Variable for calculating if employee absent clock out less than minAbsentOut then meaning employee is late and will be punished for half TKH
                        $minAbsentOut = convertToMinutes($work->endwork);

                        $empClockIn = !empty($attendBranchin->clock_in) ? convertToMinutes($attendBranchin->clock_in) : null;
                        $empClockOut = !empty($attendBranchOut->clock_out) ? convertToMinutes($attendBranchOut->clock_out) : null;

                        if (is_null($empClockIn) && !$forgetAbsentIn) {
                            $qty = 0;
                        }

                        if (is_null($empClockOut) && !$forgetAbsentOut) {
                            $qty = 0;
                        }

                        if ($empClockOut < $minAbsentOut && !$absentOut) {
                            $qty = 0;
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
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
use App\Models\M_EmpWorkDay;
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

        $md_branch_id = null;
        $md_division_id = null;
        $md_employee_id = null;
        $cutOff = 15;

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
                    'validfrom <='      => $date
                ])->orderBy('validfrom', 'ASC')->first();

                if (is_null($workDay)) {
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

                    if (is_null($work)) {
                        $qty = 0;
                        $styleCell = $style_row_dayoff;
                    } else {
                        $parAllow = [
                            'trx_allow_attendance.md_employee_id'                           => $row->md_employee_id,
                            'DATE_FORMAT(trx_allow_attendance.submissiondate, "%Y-%m-%d")'  => $date
                        ];

                        $allow = $this->model->getAllowance($parAllow)->getRow();

                        $whereClause = "v_attendance.nik = {$row->nik}";
                        $whereClause .= " AND v_attendance.date = '{$date}'";
                        $attend = $mAttendance->getAttendance($whereClause)->getRow();

                        $parAbsent = "DATE_FORMAT(trx_absent_detail.date, '%Y-%m-%d') = '{$date}'
                        AND trx_absent.docstatus = 'CO'
                        AND trx_absent.md_employee_id = {$row->md_employee_id}
                        AND trx_absent_detail.isagree = 'Y'
                        AND trx_absent.submissiontype NOT IN ({$mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari})
                        AND trx_absent.isbranch = 'N'";

                        $absent = $mAbsentDetail->getAbsentDetail($parAbsent)->getRow();

                        $parAbsent = "DATE_FORMAT(trx_absent_detail.date, '%Y-%m-%d') = '{$date}'
                            AND trx_absent.docstatus = 'CO'
                            AND trx_absent.md_employee_id = {$row->md_employee_id}
                            AND trx_absent_detail.isagree = 'Y'
                            AND trx_absent.submissiontype IN ({$mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari})
                            AND trx_absent.isbranch = 'N'";

                        $tugasNotKunjungan = $mAbsentDetail->getAbsentDetail($parAbsent)->getRow();

                        $qty = 1;

                        if ($attend) {
                            if (
                                empty($tugasNotKunjungan) && (!empty($attend->clock_in) && $attend->clock_in > "08:30")
                                || (!empty($attend->clock_out) && $attend->clock_out < "17:00")
                            ) {
                                if ($absent && $allow && ($absent->submissiontype == $mAbsent->Pengajuan_Datang_Terlambat
                                    || $absent->submissiontype == $mAbsent->Pengajuan_Pulang_Cepat)) {
                                    $qty = $qty + $allow->amount;
                                } else {
                                    $qty = 0;
                                }
                            }

                            if (empty($tugasNotKunjungan) && empty($attend->clock_in) && $attend->clock_out >= "17:00") {
                                if (
                                    $absent
                                    && $absent->enddate_realization !== "0000-00-00 00:00:00"
                                    && date("H:i", strtotime($absent->enddate_realization)) > "08:30"
                                    && $absent->submissiontype == $mAbsent->Pengajuan_Lupa_Absen_Masuk
                                ) {
                                    $qty = $qty - 0.5;
                                } else if (empty($absent)) {
                                    $qty = 0;
                                }
                            }

                            if (empty($tugasNotKunjungan) && empty($attend->clock_out) && $attend->clock_in <= "08:00") {
                                if (
                                    $absent
                                    && $absent->enddate_realization !== "0000-00-00 00:00:00"
                                    && date("H:i", strtotime($absent->enddate_realization)) < "17:30"
                                    && $absent->submissiontype == $mAbsent->Pengajuan_Lupa_Absen_Pulang
                                ) {
                                    $qty = $qty - 0.5;
                                } else if (empty($absent)) {
                                    $qty = 0;
                                }
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
                $day = date('w', strtotime($date));

                //TODO : Get work day employee
                $workDay = $mEmpWork->where([
                    'md_employee_id'    => $row->md_employee_id,
                    'validfrom <='      => $date
                ])->orderBy('validfrom', 'ASC')->first();

                if (is_null($workDay)) {
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

                    if (is_null($work)) {
                        $qty = 0;
                        $styleCell = $style_row_dayoff;
                    } else {
                        $parAllow = [
                            'trx_allow_attendance.md_employee_id'                           => $row->md_employee_id,
                            'DATE_FORMAT(trx_allow_attendance.submissiondate, "%Y-%m-%d")'  => $date
                        ];

                        $allow = $this->model->getAllowance($parAllow)->getRow();

                        $whereClause = "v_attendance.nik = {$row->nik}";
                        $whereClause .= " AND v_attendance.date = '{$date}'";
                        $attend = $mAttendance->getAttendance($whereClause)->getRow();

                        $parAbsent = "DATE_FORMAT(trx_absent_detail.date, '%Y-%m-%d') = '{$date}'
                        AND trx_absent.docstatus = 'CO'
                        AND trx_absent.md_employee_id = {$row->md_employee_id}
                        AND trx_absent_detail.isagree = 'Y'
                        AND trx_absent.submissiontype NOT IN ({$mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari})
                        AND trx_absent.isbranch = 'N'";

                        $absent = $mAbsentDetail->getAbsentDetail($parAbsent)->getRow();

                        $parAbsent = "DATE_FORMAT(trx_absent_detail.date, '%Y-%m-%d') = '{$date}'
                            AND trx_absent.docstatus = 'CO'
                            AND trx_absent.md_employee_id = {$row->md_employee_id}
                            AND trx_absent_detail.isagree = 'Y'
                            AND trx_absent.submissiontype IN ({$mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari})
                            AND trx_absent.isbranch = 'N'";

                        $tugasNotKunjungan = $mAbsentDetail->getAbsentDetail($parAbsent)->getRow();

                        $qty = 1;

                        if ($attend) {
                            if (
                                empty($tugasNotKunjungan) && (!empty($attend->clock_in) && $attend->clock_in > "08:30")
                                || (!empty($attend->clock_out) && $attend->clock_out < "17:00")
                            ) {
                                if ($absent && $allow && ($absent->submissiontype == $mAbsent->Pengajuan_Datang_Terlambat
                                    || $absent->submissiontype == $mAbsent->Pengajuan_Pulang_Cepat)) {
                                    $qty = $qty + $allow->amount;
                                } else {
                                    $qty = 0;
                                }
                            }

                            if (empty($tugasNotKunjungan) && empty($attend->clock_in) && $attend->clock_out >= "17:00") {
                                if (
                                    $absent
                                    && $absent->enddate_realization !== "0000-00-00 00:00:00"
                                    && date("H:i", strtotime($absent->enddate_realization)) > "08:30"
                                    && $absent->submissiontype == $mAbsent->Pengajuan_Lupa_Absen_Masuk
                                ) {
                                    $qty = $qty - 0.5;
                                } else if (empty($absent)) {
                                    $qty = 0;
                                }
                            }

                            if (empty($tugasNotKunjungan) && empty($attend->clock_out) && $attend->clock_in <= "08:00") {
                                if (
                                    $absent
                                    && $absent->enddate_realization !== "0000-00-00 00:00:00"
                                    && date("H:i", strtotime($absent->enddate_realization)) < "17:30"
                                    && $absent->submissiontype == $mAbsent->Pengajuan_Lupa_Absen_Pulang
                                ) {
                                    $qty = $qty - 0.5;
                                } else if (empty($absent)) {
                                    $qty = 0;
                                }
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
}

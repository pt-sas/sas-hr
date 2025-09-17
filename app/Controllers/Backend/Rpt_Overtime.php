<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Employee;
use App\Models\M_Overtime;
use App\Models\M_OvertimeDetail;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Style_Alignment;
use PHPExcel_Style_Border;
use PHPExcel_Cell_DataType;
use PHPExcel_Style_Fill;
use PHPExcel_Cell;
use PHPExcel_Worksheet_PageSetup;
use Config\Services;
use PhpCsFixer\Tokenizer\Analyzer\Analysis\StartEndTokenAwareAnalysis;

class Rpt_Overtime extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Overtime($this->request);
    }

    public function index()
    {
        $mEmployee = new M_Employee($this->request);

        $date = date('d-M-Y');
        $empList = $this->access->getEmployeeData();

        $data = [
            'date_range' => $date . ' - ' . $date,
            'ref_employee' =>  $mEmployee->select('md_employee_id, value')->whereIn('md_employee_id', $empList)->whereIn('md_status_id', [100001, 100002])->findAll(),
        ];

        return $this->template->render('report/overtime/v_overtime', $data);
    }

    public function showAll()
    {
        $post = $this->request->getPost();

        $mOvertime = new M_Overtime($this->request);

        $dates = explode(' - ', $post['date']);
        $firstDate = date('Y-m-d', strtotime($dates[0]));
        $lastDate = date('Y-m-d', strtotime($dates[1]));

        $md_branch_id = isset($post['md_branch_id']) ? implode(", ", $post['md_branch_id']) : null;
        $md_division_id = isset($post['md_division_id']) ? $md_division_id = implode(", ", $post['md_division_id']) : null;
        $md_employee_id = isset($post['md_employee_id']) ? implode(", ", $post['md_employee_id']) : implode(", ", $this->access->getEmployeeData());

        $excel = new PHPExcel();
        $excel->getProperties()->setCreator('Laporan Lembur')->setTitle("Laporan Lembur");

        $style_col = $this->createBorderStyle(true, true);
        $style_row = $this->createBorderStyle();

        $sheet = $excel->setActiveSheetIndex(0);

        $headers = [
            'A5' => ['text' => 'Nik',   'merge' => 'A5:A6'],
            'B5' => ['text' => 'Nama',  'merge' => 'B5:B6'],
            'C5' => ['text' => 'Cabang', 'merge' => 'C5:C6'],
            'D5' => ['text' => 'Divisi', 'merge' => 'D5:D6']
        ];

        foreach ($headers as $cell => $info) {
            $sheet->setCellValue($cell, $info['text']);
            $sheet->mergeCells($info['merge']);
            $sheet->getStyle($info['merge'])->applyFromArray($style_col);
        }


        $dateRange = getDatesFromRange($firstDate, $lastDate, [], 'Y-m-d', 'all');


        $colIndex = 4;
        foreach ($dateRange as $date) {
            $saldoCol  = PHPExcel_Cell::stringFromColumnIndex($colIndex);
            $jumlahCol = PHPExcel_Cell::stringFromColumnIndex($colIndex + 1);

            $sheet->setCellValue($saldoCol . '5', date('d-M-Y', strtotime($date)));
            $sheet->mergeCells($saldoCol . '5:' . $jumlahCol . '5');

            $sheet->setCellValue($saldoCol . '6', 'saldo')
                ->setCellValue($jumlahCol . '6', 'jumlah');

            $sheet->getStyle($saldoCol . '5:' . $jumlahCol . '5')->applyFromArray($style_col);
            $sheet->getStyle($saldoCol . '6')->applyFromArray($style_col);
            $sheet->getStyle($jumlahCol . '6')->applyFromArray($style_col);

            $colIndex += 2; // loncat 2 kolom per tanggal (saldo + jumlah)
        }


        $totalSaldoCol  = PHPExcel_Cell::stringFromColumnIndex($colIndex);
        $totalJumlahCol = PHPExcel_Cell::stringFromColumnIndex($colIndex + 1);

        $sheet->setCellValue($totalSaldoCol . '5', 'Total')->mergeCells($totalSaldoCol . '5:' . $totalJumlahCol . '5');
        $sheet->setCellValue($totalSaldoCol . '6', 'saldo')->setCellValue($totalJumlahCol . '6', 'jumlah');

        $sheet->getStyle($totalSaldoCol . '5:' . $totalJumlahCol . '5')->applyFromArray($style_col);
        $sheet->getStyle($totalSaldoCol . '6')->applyFromArray($style_col);
        $sheet->getStyle($totalJumlahCol . '6')->applyFromArray($style_col);


        $sheet->setCellValue('A3', "LAPORAN LEMBUR")->mergeCells("A3:{$totalJumlahCol}3");
        $sheet->getStyle("A3:{$totalJumlahCol}3")->getFont()->setBold(TRUE)->setSize(15);
        $sheet->getStyle("A3:{$totalJumlahCol}3")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);


        $whereClause = "trx_overtime.startdate BETWEEN '{$firstDate} 00:00:00' AND '{$lastDate} 23:59:59' AND trx_overtime_detail.isagree = '{$this->LINESTATUS_Disetujui}' AND trx_overtime.docstatus = '{$this->DOCSTATUS_Completed}'";
        if ($md_branch_id)   $whereClause .= " AND trx_overtime.md_branch_id IN ($md_branch_id)";
        if ($md_division_id) $whereClause .= " AND trx_overtime.md_division_id IN ($md_division_id)";
        if ($md_employee_id) $whereClause .= " AND trx_overtime_detail.md_employee_id IN ($md_employee_id)";

        $overtime = $mOvertime->getOvertimeDetail($whereClause)->getResult();

        // TODO : Pool data header & detail
        $header = [];
        $detailData = [];

        foreach ($overtime as $item) {
            if (!isset($header[$item->nik])) {
                $header[$item->nik] = $item;
            }

            $dateKey = date('Y-m-d', strtotime($item->startdate_line));
            $detailData[$item->nik][$dateKey] = $item;
        }

        $numrow = 7;
        $totalDivision = [];
        $prevBranch = $prevDivision = null;

        foreach ($header as $row) {
            $emp = $row;

            // Jika terjadi pergantian cabang/divisi, tulis total
            if (($prevBranch && $emp->branch_name !== $prevBranch) || ($prevDivision && $emp->division_name !== $prevDivision)) {
                $sheet->setCellValue("A{$numrow}", "Total Rupiah")->mergeCells("A{$numrow}:{$totalSaldoCol}{$numrow}");
                $sheet->getStyle("A{$numrow}:{$totalSaldoCol}{$numrow}")->applyFromArray($style_row)->getFont()->setBold(true);
                $sheet->getStyle("A{$numrow}:{$totalSaldoCol}{$numrow}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue("{$totalJumlahCol}{$numrow}", array_sum($totalDivision));
                $sheet->getStyle("{$totalJumlahCol}{$numrow}")->applyFromArray($style_row)->getNumberFormat()->setFormatCode("#,##0");
                $sheet->getStyle("{$totalJumlahCol}{$numrow}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("{$totalJumlahCol}{$numrow}")->getFont()->setBold(true);

                $numrow += 2;
                $totalDivision = [];
            }

            $sheet->setCellValue('A' . $numrow, $emp->nik)->setCellValue('B' . $numrow, $emp->employee_name);
            $sheet->setCellValue('C' . $numrow, $emp->branch_name)->setCellValue('D' . $numrow, $emp->division_name);
            foreach (['A', 'B', 'C', 'D'] as $c) {
                $sheet->getStyle($c . $numrow)->applyFromArray($style_row);
            }

            $cellIndex = 4;
            $prevTotal = $prevSaldo = [];

            foreach ($dateRange as $date) {
                $saldoCol  = PHPExcel_Cell::stringFromColumnIndex($cellIndex);
                $jumlahCol = PHPExcel_Cell::stringFromColumnIndex($cellIndex + 1);

                $detail = isset($detailData[$row->nik][date('Y-m-d', strtotime($date))]) ? $detailData[$row->nik][date('Y-m-d', strtotime($date))] : null;

                $saldo = $detail ? $detail->overtime_balance : 0;
                $total = $detail ? $detail->total : 0;

                $sheet->setCellValue($saldoCol . $numrow, $saldo)->setCellValue($jumlahCol . $numrow, $total);
                $sheet->getStyle($saldoCol . $numrow)->applyFromArray($style_row);
                $sheet->getStyle($jumlahCol . $numrow)->applyFromArray($style_row)->getNumberFormat()->setFormatCode("#,##0");

                $prevSaldo[] = $saldo;
                $prevTotal[] = $total;
                $totalDivision[] = $total;

                $cellIndex += 2;
            }

            $sheet->setCellValue($totalSaldoCol . $numrow, array_sum($prevSaldo));
            $sheet->setCellValue($totalJumlahCol . $numrow, array_sum($prevTotal));
            $sheet->getStyle($totalSaldoCol . $numrow)->applyFromArray($style_row);
            $sheet->getStyle($totalJumlahCol . $numrow)->applyFromArray($style_row)->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getRowDimension($numrow)->setRowHeight(20);

            $prevBranch = $emp->branch_name;
            $prevDivision = $emp->division_name;
            $numrow++;

            if ($row === end($header)) {
                $sheet->setCellValue("A{$numrow}", "Total Rupiah")->mergeCells("A{$numrow}:{$totalSaldoCol}{$numrow}");
                $sheet->getStyle("A{$numrow}:{$totalSaldoCol}{$numrow}")->applyFromArray($style_row)->getFont()->setBold(true);
                $sheet->getStyle("A{$numrow}:{$totalSaldoCol}{$numrow}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue("{$totalJumlahCol}{$numrow}", array_sum($totalDivision));
                $sheet->getStyle("{$totalJumlahCol}{$numrow}")->applyFromArray($style_row)->getNumberFormat()->setFormatCode("#,##0");
                $sheet->getStyle("{$totalJumlahCol}{$numrow}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("{$totalJumlahCol}{$numrow}")->getFont()->setBold(true);
            }
        }

        foreach (['A' => 10, 'B' => 15, 'C' => 25, 'D' => 20] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->setTitle("Laporan Lembur")->setShowGridlines(false);

        // Output
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Laporan Lembur.xlsx"');
        header('Cache-Control: max-age=0');
        PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save('php://output');
        exit();
    }

    /**
     * Report Lemburan Harian
     */
    public function indexDaily()
    {
        $mEmployee = new M_Employee($this->request);

        $date = date('d-M-Y');
        $empList = $this->access->getEmployeeData();

        $data = [
            'date' => $date,
            'ref_employee' =>  $mEmployee->select('md_employee_id, value')->whereIn('md_employee_id', $empList)->whereIn('md_status_id', [100001, 100002])->findAll(),
        ];

        return $this->template->render('report/overtimedaily/v_overtime_daily', $data);
    }

    public function showAllDaily()
    {
        $post = $this->request->getPost();

        $date = date('Y-m-d', strtotime($post['date']));

        $md_branch_id = isset($post['md_branch_id']) ? implode(", ", $post['md_branch_id']) : null;
        $md_division_id = isset($post['md_division_id']) ? $md_division_id = implode(", ", $post['md_division_id']) : null;
        $md_employee_id = isset($post['md_employee_id']) ? implode(", ", $post['md_employee_id']) : implode(", ", $this->access->getEmployeeData());

        // Panggil class PHPExcel nya
        $excel = new PHPExcel();
        // Settingan awal file excel
        $excel->getProperties()->setCreator('Laporan Lembur Harian')->setTitle("Laporan Lembur Harian");
        // Buat sebuah variabel untuk menampung pengaturan style dari header tabel
        $style_col = $this->createBorderStyle(true, true);
        // Buat sebuah variabel untuk menampung pengaturan style dari isi tabel
        $style_row = $this->createBorderStyle();

        $sheet = $excel->setActiveSheetIndex(0);

        $sheet->setCellValue('I1', $post['date'])->getStyle('I1')->getFont()->setBold(true)->setSize(15);
        $sheet->getStyle('I1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

        $sheet->setCellValue('A3', "LAPORAN LEMBUR HARIAN")->mergeCells('A3:I3'); // Set kolom A1 dengan tulisan "LAPORAN ABSENSI HARIAN"
        $sheet->getStyle('A3:H3')->getFont()->setBold(TRUE)->setSize(15); // Set bold kolom A1
        $sheet->getStyle('A3:H3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER); // Set text center untuk kolom A1

        $headers = ['A5' => 'Nik', 'B5' => 'Nama', 'C5' => 'Cabang', 'D5' => 'Divisi', 'E5' => 'Jam Mulai', 'F5' => 'Jam Selesai', 'G5' => 'Saldo', 'H5' => 'Jumlah', 'I5' => 'Tanda Tangan'];

        foreach ($headers as $cell => $info) {
            $sheet->setCellValue($cell, $info);
            $sheet->getStyle($cell)->applyFromArray($style_col);
        }

        $whereClause = "trx_overtime.startdate BETWEEN '{$date} 00:00:00' AND '{$date} 23:59:59'";
        $whereClause .= " AND trx_overtime_detail.isagree = '{$this->LINESTATUS_Disetujui}'";
        $whereClause .= " AND trx_overtime.docstatus = '{$this->DOCSTATUS_Completed}'";
        if ($md_branch_id)   $whereClause .= " AND trx_overtime.md_branch_id IN ($md_branch_id)";
        if ($md_division_id) $whereClause .= " AND trx_overtime.md_division_id IN ($md_division_id)";
        if ($md_employee_id) $whereClause .= " AND trx_overtime_detail.md_employee_id IN ($md_employee_id)";

        $overtime = $this->model->getOvertimeDetail($whereClause)->getResult();

        $numrow = 6; // Set baris pertama untuk isi tabel adalah baris ke 6
        $column = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];

        foreach ($overtime as $row) {

            $sheet->setCellValue('A' . $numrow, $row->nik);
            $sheet->setCellValue('B' . $numrow, $row->employee_name);
            $sheet->setCellValue('C' . $numrow, $row->branch_name);
            $sheet->setCellValue('D' . $numrow, $row->division_name);
            $sheet->setCellValue('E' . $numrow, format_time($row->startdate_line));
            $sheet->setCellValue('F' . $numrow, format_time($row->enddate_realization));
            $sheet->setCellValue('G' . $numrow, $row->overtime_balance);
            $sheet->setCellValue('H' . $numrow, $row->total);

            // Apply style row yang telah kita buat tadi ke masing-masing baris (isi tabel)
            // $excel->getActiveSheet()->getStyle('A' . $numrow)->applyFromArray($style_row);
            // $excel->getActiveSheet()->getStyle('B' . $numrow)->applyFromArray($style_row);
            // $excel->getActiveSheet()->getStyle('C' . $numrow)->applyFromArray($style_row);
            // $excel->getActiveSheet()->getStyle('D' . $numrow)->applyFromArray($style_row);
            // $excel->getActiveSheet()->getStyle('E' . $numrow)->applyFromArray($style_row);
            // $excel->getActiveSheet()->getStyle('F' . $numrow)->applyFromArray($style_row);
            // $excel->getActiveSheet()->getStyle('G' . $numrow)->applyFromArray($style_row);
            // $excel->getActiveSheet()->getStyle('H' . $numrow)->applyFromArray($style_row)->getNumberFormat()->setFormatCode("#,##0");
            // $excel->getActiveSheet()->getStyle('I' . $numrow)->applyFromArray($style_row);

            foreach ($column as $c) {
                $sheet->getStyle($c . $numrow)->applyFromArray($style_row);

                if ($c == 'H') {
                    $sheet->getStyle($c . $numrow)->getNumberFormat()->setFormatCode("#,##0");
                }
            }

            $numrow++; // Tambah 1 setiap kali looping
        }
        // Set width kolom
        $sheet->getColumnDimension('A')->setWidth(10); // Set width kolom A
        $sheet->getColumnDimension('B')->setWidth(15); // Set width kolom B
        $sheet->getColumnDimension('C')->setWidth(25); // Set width kolom C
        $sheet->getColumnDimension('D')->setWidth(20); // Set width kolom D
        $sheet->getColumnDimension('E')->setWidth(15); // Set width kolom C
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(30);
        // Set orientasi kertas jadi LANDSCAPE
        $sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
        // Set judul file excel nya
        $sheet->setShowGridlines(false);
        // Proses file excel
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Laporan Lembur Harian.xlsx"'); // Set nama file excel nya
        header('Cache-Control: max-age=0');
        $write = PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save('php://output');
        exit();
    }

    /**
     * Report Lemburan Mingguan
     */
    public function indexWeekly()
    {
        $mEmployee = new M_Employee($this->request);

        $start = date('d-M-Y', strtotime("this week"));
        $end = date('d-M-Y', strtotime("this sunday"));
        $empList = $this->access->getEmployeeData();

        $data = [
            'week' => $start . ' - ' . $end,
            'ref_employee' =>  $mEmployee->select('md_employee_id, value')->whereIn('md_employee_id', $empList)->whereIn('md_status_id', [100001, 100002])->findAll(),
        ];

        return $this->template->render('report/overtimeweekly/v_overtime_weekly', $data);
    }

    public function showAllWeekly()
    {
        $post = $this->request->getPost();

        $mOvertime = new M_Overtime($this->request);

        $dates = explode(' - ', $post['date']);
        $firstDate = date('Y-m-d', strtotime($dates[0]));
        $lastDate = date('Y-m-d', strtotime($dates[1]));

        $md_branch_id = isset($post['md_branch_id']) ? implode(", ", $post['md_branch_id']) : null;
        $md_division_id = isset($post['md_division_id']) ? $md_division_id = implode(", ", $post['md_division_id']) : null;
        $md_employee_id = isset($post['md_employee_id']) ? implode(", ", $post['md_employee_id']) : implode(", ", $this->access->getEmployeeData());

        // Panggil class PHPExcel nya
        $excel = new PHPExcel();
        // Settingan awal file excel
        $excel->getProperties()->setCreator('Laporan Lembur Mingguan')->setTitle("Laporan Lembur Mingguan");

        // Style untuk header kolom (teks bold dan rata tengah)
        $style_col = $this->createBorderStyle(true, true);

        // Style untuk baris normal
        $style_row = $this->createBorderStyle();

        $sheet = $excel->setActiveSheetIndex(0);

        $sheet->setCellValue('T1', $post['date'])->getStyle('T1')->getFont()->setBold(TRUE)->setSize(15);
        $sheet->getStyle('T1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

        $sheet->setCellValue('A3', "LAPORAN LEMBUR MINGGUAN")->mergeCells('A3:T3'); // Set kolom A1 dengan tulisan "LAPORAN ABSENSI HARIAN"
        $sheet->getStyle('A3:T3')->getFont()->setBold(TRUE); // Set bold kolom A1
        $sheet->getStyle('A3:T3')->getFont()->setBold(true)->setSize(15);
        $sheet->getStyle('A3:T3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        //TODO : Header kolom dan merge cell
        $headers = [
            'A5' => ['text' => 'Nik', 'merge' => 'A5:A6'],
            'B5' => ['text' => 'Nama', 'merge' => 'B5:B6'],
            'C5' => ['text' => 'Cabang', 'merge' => 'C5:C6'],
            'D5' => ['text' => 'Divisi', 'merge' => 'D5:D6']
        ];

        foreach ($headers as $cell => $info) {
            $sheet->setCellValue($cell, $info['text']);
            $sheet->mergeCells($info['merge']);
            $sheet->getStyle($info['merge'])->applyFromArray($style_col);
        }

        // Set dynamic date headers
        $dateRange = getDatesFromRange($firstDate, $lastDate, [], 'Y-m-d', 'all');
        $col = 'E';
        foreach ($dateRange as $date) {
            $nextCol = chr(ord($col) + 1);
            $sheet->setCellValue($col . '5', date('d-M-Y', strtotime($date)))->mergeCells($col . '5:' . $nextCol . '5');
            $sheet->setCellValue($col . '6', 'saldo')->setCellValue($nextCol . '6', 'jumlah');
            $sheet->getStyle($col . '5:' . $nextCol . '5')->applyFromArray($style_col);
            $sheet->getStyle($col . '6')->applyFromArray($style_col);
            $sheet->getStyle($nextCol . '6')->applyFromArray($style_col);
            $col = chr(ord($col) + 2);
        }

        // Set total column
        $totalSaldoCol = $col;
        $totalJumlahCol = chr(ord($col) + 1);
        $sheet->setCellValue($col . '5', 'Total')->mergeCells($col . '5:' . $totalJumlahCol . '5');
        $sheet->setCellValue($col . '6', 'saldo')->setCellValue($totalJumlahCol . '6', 'jumlah');
        $sheet->getStyle($col . '5:' . $totalJumlahCol . '5')->applyFromArray($style_col);
        $sheet->getStyle($col . '6')->applyFromArray($style_col);
        $sheet->getStyle($totalJumlahCol . '6')->applyFromArray($style_col);

        // Build where clause and get data
        $whereClause = "trx_overtime.startdate BETWEEN '{$firstDate} 00:00:00' AND '{$lastDate} 23:59:59' AND trx_overtime_detail.isagree = '{$this->LINESTATUS_Disetujui}' AND trx_overtime.docstatus = '{$this->DOCSTATUS_Completed}'";
        if ($md_branch_id) $whereClause .= " AND trx_overtime.md_branch_id IN ($md_branch_id)";
        if ($md_division_id) $whereClause .= " AND trx_overtime.md_division_id IN ($md_division_id)";
        if ($md_employee_id) $whereClause .= " AND trx_overtime_detail.md_employee_id IN ($md_employee_id)";

        $overtime = $mOvertime->getOvertimeDetail($whereClause)->getResult();

        $header = [];
        $detailData = [];

        foreach ($overtime as $item) {
            if (!isset($header[$item->nik])) {
                $header[$item->nik] = $item;
            }

            $dateKey = date('Y-m-d', strtotime($item->startdate_line));
            $detailData[$item->nik][$dateKey] = $item;
        }

        $numrow = 7;
        $totalDivision = [];
        $prevBranch = $prevDivision = null;

        foreach ($header as $row) {
            $emp = $row;

            // Check for division change and insert total
            if (($prevBranch && $emp->branch_name !== $prevBranch) || ($prevDivision && $emp->division_name !== $prevDivision)) {
                $sheet->setCellValue("A{$numrow}", "Total Rupiah")->mergeCells("A{$numrow}:{$totalSaldoCol}{$numrow}");
                $sheet->getStyle("A{$numrow}:{$totalSaldoCol}{$numrow}")->applyFromArray($style_row)->getFont()->setBold(true);
                $sheet->getStyle("A{$numrow}:{$totalSaldoCol}{$numrow}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue("{$totalJumlahCol}{$numrow}", array_sum($totalDivision));
                $sheet->getStyle("{$totalJumlahCol}{$numrow}")->applyFromArray($style_row)->getNumberFormat()->setFormatCode("#,##0");
                $sheet->getStyle("{$totalJumlahCol}{$numrow}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("{$totalJumlahCol}{$numrow}")->getFont()->setBold(true);
                $numrow += 2;
                $totalDivision = [];
            }

            // Set employee basic info
            $sheet->setCellValue('A' . $numrow, $emp->nik)->setCellValue('B' . $numrow, $emp->employee_name);
            $sheet->setCellValue('C' . $numrow, $emp->branch_name)->setCellValue('D' . $numrow, $emp->division_name);
            foreach (['A', 'B', 'C', 'D'] as $c) {
                $sheet->getStyle($c . $numrow)->applyFromArray($style_row);
            }

            // Fill overtime data for each date
            $cellSaldo = 'E';
            $cellTotal = 'F';
            $prevTotal = $prevSaldo = [];

            foreach ($dateRange as $date) {
                $detail = isset($detailData[$row->nik][date('Y-m-d', strtotime($date))]) ? $detailData[$row->nik][date('Y-m-d', strtotime($date))] : null;

                $saldo = $detail ? $detail->overtime_balance : 0;
                $total = $detail ? $detail->total : 0;

                $sheet->setCellValue($cellSaldo . $numrow, $saldo)->setCellValue($cellTotal . $numrow, $total);
                $sheet->getStyle($cellSaldo . $numrow)->applyFromArray($style_row);
                $sheet->getStyle($cellTotal . $numrow)->applyFromArray($style_row)->getNumberFormat()->setFormatCode("#,##0");

                $prevSaldo[] = $saldo;
                $prevTotal[] = $total;
                $totalDivision[] = $total;
                $cellSaldo = chr(ord($cellSaldo) + 2);
                $cellTotal = chr(ord($cellTotal) + 2);
            }

            // Set row totals
            $sheet->setCellValue($totalSaldoCol . $numrow, array_sum($prevSaldo));
            $sheet->setCellValue($totalJumlahCol . $numrow, array_sum($prevTotal));
            $sheet->getStyle($totalSaldoCol . $numrow)->applyFromArray($style_row);
            $sheet->getStyle($totalJumlahCol . $numrow)->applyFromArray($style_row)->getNumberFormat()->setFormatCode("#,##0");
            $sheet->getRowDimension($numrow)->setRowHeight(20);

            $prevBranch = $emp->branch_name;
            $prevDivision = $emp->division_name;
            $numrow++;

            // Insert final total if last row
            if ($row === end($header)) {
                $sheet->setCellValue("A{$numrow}", "Total Rupiah")->mergeCells("A{$numrow}:{$totalSaldoCol}{$numrow}");
                $sheet->getStyle("A{$numrow}:{$totalSaldoCol}{$numrow}")->applyFromArray($style_row)->getFont()->setBold(true);
                $sheet->getStyle("A{$numrow}:{$totalSaldoCol}{$numrow}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $sheet->setCellValue("{$totalJumlahCol}{$numrow}", array_sum($totalDivision));
                $sheet->getStyle("{$totalJumlahCol}{$numrow}")->applyFromArray($style_row)->getNumberFormat()->setFormatCode("#,##0");
                $sheet->getStyle("{$totalJumlahCol}{$numrow}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("{$totalJumlahCol}{$numrow}")->getFont()->setBold(true);
            }
        }

        // Set final Excel properties and output
        foreach (['A' => 10, 'B' => 15, 'C' => 25, 'D' => 20] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        $sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->setTitle("Laporan Lembur Mingguan")->setShowGridlines(false);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Laporan Lembur Mingguan.xlsx"');
        header('Cache-Control: max-age=0');
        PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save('php://output');
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
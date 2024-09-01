<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Overtime;
use App\Models\M_OvertimeDetail;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Style_Alignment;
use PHPExcel_Style_Border;
use PHPExcel_Cell_DataType;
use PHPExcel_Style_Fill;
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
        $date = date('d-M-Y');
        $data = [
            'date_range' => $date . ' - ' . $date
        ];

        return $this->template->render('report/overtime/v_overtime', $data);
    }

    public function showAll()
    {
        $post = $this->request->getPost();

        $mOvertime = new M_Overtime($this->request);
        $mOvertimeDetail = new M_OvertimeDetail($this->request);

        $dates = explode(' - ', $post['date']);
        $firstDate = date('Y-m-d', strtotime($dates[0]));
        $lastDate = date('Y-m-d', strtotime($dates[1]));

        if (isset($post['md_branch_id']))
            $md_branch_id = implode(", ", $post['md_branch_id']);

        if (isset($post['md_division_id']))
            $md_division_id = implode(", ", $post['md_division_id']);

        if (isset($post['md_employee_id']))
            $md_employee_id = implode(", ", $post['md_employee_id']);

        // Panggil class PHPExcel nya
        $excel = new PHPExcel();
        // Settingan awal file excel
        $excel->getProperties()->setCreator('Laporan Lembur')
            ->setLastModifiedBy('Laporan Lembur')
            ->setTitle("Laporan Lembur")
            ->setSubject("Laporan Lembur")
            ->setDescription("Laporan Lembur")
            ->setKeywords("Laporan Lembur");
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

        /* 
        Set Up Header Table for Date, Start From here
        */
        $excel->setActiveSheetIndex(0)->setCellValue('A5', "Nik"); // Set kolom A5 dengan tulisan "Nik"
        $excel->getActiveSheet()->mergeCells('A5:A6');
        $excel->getActiveSheet()->getStyle('A5:A6')->applyFromArray($style_col); // Set Merge Cell
        $excel->setActiveSheetIndex(0)->setCellValue('B5', "Nama"); // Set kolom B5 dengan tulisan "Nama"
        $excel->getActiveSheet()->mergeCells('B5:B6');
        $excel->getActiveSheet()->getStyle('B5:B6')->applyFromArray($style_col); // Set Merge Cell
        $excel->setActiveSheetIndex(0)->setCellValue('C5', "Cabang"); // Set kolom C5 dengan tulisan "Cabang"
        $excel->getActiveSheet()->mergeCells('C5:C6');
        $excel->getActiveSheet()->getStyle('C5:C6')->applyFromArray($style_col); // Set Merge Cell
        $excel->setActiveSheetIndex(0)->setCellValue('D5', "Divisi"); // Set kolom D5 dengan tulisan "Divisi"
        $excel->getActiveSheet()->mergeCells('D5:D6');
        $excel->getActiveSheet()->getStyle('D5:D6')->applyFromArray($style_col); // Set Merge Cell

        $dateRange = getDatesFromRange($firstDate, $lastDate, [], 'Y-m-d', 'all');
        $saldoCol = 'E';
        $jumlahCol = 'F';

        foreach ($dateRange as $date) {
            $excel->setActiveSheetIndex(0)->setCellValue("{$saldoCol}5", date('d-M-Y', strtotime($date))); // Set kolom E5 dengan Tanggal Pertama
            $excel->setActiveSheetIndex(0)->setCellValue("{$saldoCol}6", 'saldo');
            $excel->setActiveSheetIndex(0)->setCellValue("{$jumlahCol}6", 'jumlah');
            $excel->getActiveSheet()->getStyle("{$saldoCol}6")->applyFromArray($style_col);
            $excel->getActiveSheet()->getStyle("{$jumlahCol}6")->applyFromArray($style_col);

            $excel->getActiveSheet()->mergeCells("{$saldoCol}5:{$jumlahCol}5"); // Set Merge Cell
            $excel->getActiveSheet()->getStyle("{$saldoCol}5:{$jumlahCol}5")->applyFromArray($style_col);

            $saldoCol++;
            $saldoCol++;
            $jumlahCol++;
            $jumlahCol++;
        }

        $excel->setActiveSheetIndex(0)->setCellValue("{$saldoCol}5", 'Total'); // Set kolom Total
        $excel->setActiveSheetIndex(0)->setCellValue("{$saldoCol}6", 'saldo');
        $excel->setActiveSheetIndex(0)->setCellValue("{$jumlahCol}6", 'jumlah');
        $excel->getActiveSheet()->getStyle("{$saldoCol}6")->applyFromArray($style_col);
        $excel->getActiveSheet()->getStyle("{$jumlahCol}6")->applyFromArray($style_col);

        $excel->getActiveSheet()->mergeCells("{$saldoCol}5:{$jumlahCol}5"); // Set Merge Cell
        $excel->getActiveSheet()->getStyle("{$saldoCol}5:{$jumlahCol}5")->applyFromArray($style_col);

        $excel->setActiveSheetIndex(0)->setCellValue("{$jumlahCol}1", $post['date']);
        $excel->getActiveSheet()->getStyle("{$jumlahCol}1")->getFont()->setBold(TRUE);
        $excel->getActiveSheet()->getStyle("{$jumlahCol}1")->getFont()->setSize(15);
        $excel->getActiveSheet()->getStyle("{$jumlahCol}1")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

        $excel->setActiveSheetIndex(0)->setCellValue('A3', "LAPORAN LEMBUR"); // Set kolom A1 dengan tulisan "LAPORAN ABSENSI HARIAN"
        $excel->getActiveSheet()->mergeCells("A3:{$jumlahCol}3");
        $excel->getActiveSheet()->getStyle("A3:{$jumlahCol}3")->getFont()->setBold(TRUE); // Set bold kolom A1
        $excel->getActiveSheet()->getStyle("A3:{$jumlahCol}3")->getFont()->setSize(15); // Set font size 15 untuk kolom A1
        $excel->getActiveSheet()->getStyle("A3:{$jumlahCol}3")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER); // Set text center untuk kolom A1

        $whereClause = "trx_overtime.startdate BETWEEN '{$firstDate} 00:00:00' AND '{$lastDate} 23:59:59'";
        $whereClause .= " AND trx_overtime_detail.status = 'Y'";
        $whereClause .= " AND trx_overtime.docstatus = 'CO'";

        if (isset($md_branch_id)) {
            $whereClause .= " AND trx_overtime.md_branch_id IN ($md_branch_id)";
        }

        if (isset($md_division_id)) {
            $whereClause .= " AND trx_overtime.md_division_id IN ($md_division_id)";
        }

        if (isset($md_employee_id)) {
            $whereClause .= " AND trx_overtime_detail.md_employee_id IN ($md_employee_id)";
        }

        $overtime = $mOvertime->getOvertimeDetail($whereClause)->getResult();

        $header = [];

        foreach ($overtime as $item) {
            $header[$item->nik][] = $item;
        }

        $numrow = 7; // Set baris pertama untuk isi tabel adalah baris ke 7
        $prevBranch = null;
        $prevDivision = null;

        // foreach ($header as $value) {
        $totalDivision = [];
        foreach ($header as $row) {

            if (($prevBranch && $row[0]->branch_name !== $prevBranch) || ($prevDivision && $row[0]->division_name !== $prevDivision)) {
                $excel->setActiveSheetIndex(0)->setCellValue("A{$numrow}", "Total Rupiah",);
                $excel->getActiveSheet()->mergeCells("A{$numrow}:$cellSaldo{$numrow}");
                $excel->getActiveSheet()->getStyle("A{$numrow}:$cellSaldo{$numrow}")->applyFromArray($styleCell);
                $excel->getActiveSheet()->getStyle("A{$numrow}:$cellSaldo{$numrow}")->getFont()->setBold(TRUE);
                $excel->getActiveSheet()->getStyle("A{$numrow}:$cellSaldo{$numrow}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

                $excel->setActiveSheetIndex(0)->setCellValue("$cellTotal{$numrow}", array_sum($totalDivision));
                $excel->getActiveSheet()->getStyle("$cellTotal{$numrow}")->applyFromArray($styleCell)->getNumberFormat()->setFormatCode("#,##0");
                $excel->getActiveSheet()->getStyle("$cellTotal{$numrow}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $excel->getActiveSheet()->getStyle("$cellTotal{$numrow}")->getFont()->setBold(TRUE);

                $numrow++;
                $numrow++;
                $totalDivision = [];
            }

            $excel->setActiveSheetIndex(0)->setCellValue('A' . $numrow, $row[0]->nik);
            $excel->setActiveSheetIndex(0)->setCellValue('B' . $numrow, $row[0]->employee_name);
            $excel->setActiveSheetIndex(0)->setCellValue('C' . $numrow, $row[0]->branch_name);
            $excel->setActiveSheetIndex(0)->setCellValue('D' . $numrow, $row[0]->division_name);

            // Apply style row yang telah kita buat tadi ke masing-masing baris (isi tabel)
            $excel->getActiveSheet()->getStyle('A' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('B' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('C' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('D' . $numrow)->applyFromArray($style_row);

            $cellSaldo = 'E';
            $cellTotal = 'F';

            $prevTotal = [];
            $prevSaldo = [];
            foreach ($dateRange as $date) {
                $tanggal = date('Y-m-d H:i:s', strtotime($date));
                $tglend =
                    date('Y-m-d 23:59:59', strtotime($date));

                $detail = $mOvertimeDetail->where(['md_employee_id' => $row[0]->md_employee_id, 'startdate >' => $tanggal, 'status' => 'Y', 'startdate <' => $tglend])->first();

                $styleCell = $style_row;

                $saldo = 0;
                $total = 0;

                if ($detail) {
                    $saldo = $detail->overtime_balance;
                    $total = $detail->total;
                }

                $excel->setActiveSheetIndex(0)->setCellValue($cellSaldo . $numrow, $saldo); // For Set Value Overtime Balance
                $excel->setActiveSheetIndex(0)->setCellValue($cellTotal . $numrow, $total); // For Set Value Total
                $excel->getActiveSheet()->getStyle($cellSaldo . $numrow)->applyFromArray($styleCell);
                $excel->getActiveSheet()->getStyle($cellTotal . $numrow)->applyFromArray($styleCell)->getNumberFormat()->setFormatCode("#,##0");

                $prevSaldo[] = $saldo;
                $prevTotal[] = $total;
                $totalDivision[] = $total;
                $cellSaldo++;
                $cellSaldo++;

                $cellTotal++;
                $cellTotal++;
            }

            $excel->setActiveSheetIndex(0)->setCellValue($cellSaldo . $numrow, array_sum($prevSaldo));
            $excel->getActiveSheet()->getStyle($cellSaldo . $numrow)->applyFromArray($styleCell);

            $excel->setActiveSheetIndex(0)->setCellValue($cellTotal . $numrow, array_sum($prevTotal));
            $excel->getActiveSheet()->getStyle($cellTotal . $numrow)->applyFromArray($styleCell)->getNumberFormat()->setFormatCode("#,##0");
            $excel->getActiveSheet()->getRowDimension($numrow)->setRowHeight(20);

            $numrow++; // Tambah 1 setiap kali looping

            $prevBranch = $row[0]->branch_name;
            $prevDivision = $row[0]->division_name;

            if ($row === end($header)) {
                $excel->setActiveSheetIndex(0)->setCellValue("A{$numrow}", "Total Rupiah",);
                $excel->getActiveSheet()->mergeCells("A{$numrow}:$cellSaldo{$numrow}");
                $excel->getActiveSheet()->getStyle("A{$numrow}:$cellSaldo{$numrow}")->applyFromArray($styleCell);
                $excel->getActiveSheet()->getStyle("A{$numrow}:$cellSaldo{$numrow}")->getFont()->setBold(TRUE);
                $excel->getActiveSheet()->getStyle("A{$numrow}:$cellSaldo{$numrow}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

                $excel->setActiveSheetIndex(0)->setCellValue("$cellTotal{$numrow}", array_sum($totalDivision));
                $excel->getActiveSheet()->getStyle("$cellTotal{$numrow}")->applyFromArray($styleCell)->getNumberFormat()->setFormatCode("#,##0");
                $excel->getActiveSheet()->getStyle("$cellTotal{$numrow}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $excel->getActiveSheet()->getStyle("$cellTotal{$numrow}")->getFont()->setBold(TRUE);
            }
        }
        // Set width kolom
        $excel->getActiveSheet()->getColumnDimension('A')->setWidth(10); // Set width kolom A
        $excel->getActiveSheet()->getColumnDimension('B')->setWidth(15); // Set width kolom B
        $excel->getActiveSheet()->getColumnDimension('C')->setWidth(25); // Set width kolom C
        $excel->getActiveSheet()->getColumnDimension('D')->setWidth(20); // Set width kolom D
        // Set orientasi kertas jadi LANDSCAPE
        $excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
        // Set judul file excel nya
        $excel->getActiveSheet(0)->setTitle("Laporan Lembur");
        $excel->setActiveSheetIndex(0)->setShowGridlines(false);
        // Proses file excel
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Laporan Lembur.xlsx"'); // Set nama file excel nya
        header('Cache-Control: max-age=0');
        $write = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $write->save('php://output');
        exit();
    }

    /**
     * Report Lemburan Harian
     */
    public function indexDaily()
    {
        $date = date('d-M-Y');

        $data = [
            'date' => $date
        ];

        return $this->template->render('report/overtimedaily/v_overtime_daily', $data);
    }

    public function showAllDaily()
    {
        $post = $this->request->getPost();

        $date = date('Y-m-d', strtotime($post['date']));

        if (isset($post['md_branch_id']))
            $md_branch_id = implode(", ", $post['md_branch_id']);

        if (isset($post['md_division_id']))
            $md_division_id = implode(", ", $post['md_division_id']);

        if (isset($post['md_employee_id']))
            $md_employee_id = implode(", ", $post['md_employee_id']);

        // Panggil class PHPExcel nya
        $excel = new PHPExcel();
        // Settingan awal file excel
        $excel->getProperties()->setCreator('Laporan Lembur Harian')
            ->setLastModifiedBy('Laporan Lembur Harian')
            ->setTitle("Laporan Lembur Harian")
            ->setSubject("Laporan Lembur Harian")
            ->setDescription("Laporan Lembur Harian")
            ->setKeywords("Laporan Lembur Harian");
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

        $excel->setActiveSheetIndex(0)->setCellValue('I1', $post['date']);
        $excel->getActiveSheet()->getStyle('I1')->getFont()->setBold(TRUE);
        $excel->getActiveSheet()->getStyle('I1')->getFont()->setSize(15);
        $excel->getActiveSheet()->getStyle('I1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

        $excel->setActiveSheetIndex(0)->setCellValue('A3', "LAPORAN LEMBUR HARIAN"); // Set kolom A1 dengan tulisan "LAPORAN ABSENSI HARIAN"
        $excel->getActiveSheet()->mergeCells('A3:I3');
        $excel->getActiveSheet()->getStyle('A3:H3')->getFont()->setBold(TRUE); // Set bold kolom A1
        $excel->getActiveSheet()->getStyle('A3:H3')->getFont()->setSize(15); // Set font size 15 untuk kolom A1
        $excel->getActiveSheet()->getStyle('A3:H3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER); // Set text center untuk kolom A1
        // Buat header tabel nya pada baris ke 3
        $excel->setActiveSheetIndex(0)->setCellValue('A5', "Nik"); // Set kolom A3 dengan tulisan "NIK"
        $excel->getActiveSheet()->getStyle('A5')->applyFromArray($style_col); // Set Merge Cell
        $excel->setActiveSheetIndex(0)->setCellValue('B5', "Nama"); // Set kolom B3 dengan tulisan "Nama"
        $excel->getActiveSheet()->getStyle('B5')->applyFromArray($style_col); // Set Merge Cell
        $excel->setActiveSheetIndex(0)->setCellValue('C5', "Cabang"); // Set kolom C3 dengan tulisan "Cabang"
        $excel->getActiveSheet()->getStyle('C5')->applyFromArray($style_col); // Set Merge Cell
        $excel->setActiveSheetIndex(0)->setCellValue('D5', "Divisi"); // Set kolom D3 dengan tulisan "Divisi"
        $excel->getActiveSheet()->getStyle('D5')->applyFromArray($style_col); // Set Merge Cell
        $excel->setActiveSheetIndex(0)->setCellValue('E5', "Jam Mulai"); // Set kolom D3 dengan tulisan "Divisi"
        $excel->getActiveSheet()->getStyle('E5')->applyFromArray($style_col); // Set Merge Cell
        $excel->setActiveSheetIndex(0)->setCellValue('F5', "Jam Selesai"); // Set kolom D3 dengan tulisan "Divisi"
        $excel->getActiveSheet()->getStyle('F5')->applyFromArray($style_col); // Set Merge Cell
        $excel->setActiveSheetIndex(0)->setCellValue('G5', "Saldo"); // Set kolom D3 dengan tulisan "Divisi"
        $excel->getActiveSheet()->getStyle('G5')->applyFromArray($style_col); // Set Merge Cell
        $excel->setActiveSheetIndex(0)->setCellValue('H5', "Jumlah"); // Set kolom D3 dengan tulisan "Divisi"
        $excel->getActiveSheet()->getStyle('H5')->applyFromArray($style_col); // Set Merge Cell
        $excel->setActiveSheetIndex(0)->setCellValue('I5', "Tanda Tangan"); // Set kolom D3 dengan tulisan "Divisi"
        $excel->getActiveSheet()->getStyle('I5')->applyFromArray($style_col); // Set Merge Cell

        $whereClause = "trx_overtime.startdate BETWEEN '{$date} 00:00:00' AND '{$date} 23:59:59'";
        $whereClause .= " AND trx_overtime_detail.status = 'Y'";
        $whereClause .= " AND trx_overtime.docstatus = 'CO'";

        if (isset($md_branch_id)) {
            $whereClause .= " AND trx_overtime.md_branch_id IN ($md_branch_id)";
        }

        if (isset($md_division_id)) {
            $whereClause .= " AND trx_overtime.md_division_id IN ($md_division_id)";
        }

        if (isset($md_employee_id)) {
            $whereClause .= " AND trx_overtime_detail.md_employee_id IN ($md_employee_id)";
        }

        $overtime = $this->model->getOvertimeDetail($whereClause)->getResult();

        $numrow = 6; // Set baris pertama untuk isi tabel adalah baris ke 6


        foreach ($overtime as $row) {

            $excel->setActiveSheetIndex(0)->setCellValue('A' . $numrow, $row->nik);
            $excel->setActiveSheetIndex(0)->setCellValue('B' . $numrow, $row->employee_name);
            $excel->setActiveSheetIndex(0)->setCellValue('C' . $numrow, $row->branch_name);
            $excel->setActiveSheetIndex(0)->setCellValue('D' . $numrow, $row->division_name);
            $excel->setActiveSheetIndex(0)->setCellValue('E' . $numrow, format_time($row->startdate_line));
            $excel->setActiveSheetIndex(0)->setCellValue('F' . $numrow, format_time($row->enddate_realization));
            $excel->setActiveSheetIndex(0)->setCellValue('G' . $numrow, $row->overtime_balance);
            $excel->setActiveSheetIndex(0)->setCellValue('H' . $numrow, $row->total);

            // Apply style row yang telah kita buat tadi ke masing-masing baris (isi tabel)
            $excel->getActiveSheet()->getStyle('A' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('B' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('C' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('D' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('E' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('F' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('G' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('H' . $numrow)->applyFromArray($style_row)->getNumberFormat()->setFormatCode("#,##0");
            $excel->getActiveSheet()->getStyle('I' . $numrow)->applyFromArray($style_row);

            $numrow++; // Tambah 1 setiap kali looping
        }
        // Set width kolom
        $excel->getActiveSheet()->getColumnDimension('A')->setWidth(10); // Set width kolom A
        $excel->getActiveSheet()->getColumnDimension('B')->setWidth(15); // Set width kolom B
        $excel->getActiveSheet()->getColumnDimension('C')->setWidth(25); // Set width kolom C
        $excel->getActiveSheet()->getColumnDimension('D')->setWidth(20); // Set width kolom D
        $excel->getActiveSheet()->getColumnDimension('E')->setWidth(15); // Set width kolom C
        $excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $excel->getActiveSheet()->getColumnDimension('I')->setWidth(30);
        // Set orientasi kertas jadi LANDSCAPE
        $excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
        // Set judul file excel nya
        $excel->getActiveSheet(0)->setTitle("Laporan Lembur Harian");
        $excel->setActiveSheetIndex(0)->setShowGridlines(false);
        // Proses file excel
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Laporan Lembur Harian.xlsx"'); // Set nama file excel nya
        header('Cache-Control: max-age=0');
        $write = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $write->save('php://output');
        exit();
    }

    /**
     * Report Lemburan Mingguan
     */
    public function indexWeekly()
    {
        $start = date('d-M-Y', strtotime("this week"));
        $end =
            date('d-M-Y', strtotime("this sunday"));
        $data = [
            'week' => $start . ' - ' . $end
        ];

        return $this->template->render('report/overtimeweekly/v_overtime_weekly', $data);
    }

    public function showAllWeekly()
    {
        $post = $this->request->getPost();

        $mOvertime = new M_Overtime($this->request);
        $mOvertimeDetail = new M_OvertimeDetail($this->request);

        $dates = explode(' - ', $post['date']);
        $firstDate = date('Y-m-d', strtotime($dates[0]));
        $lastDate = date('Y-m-d', strtotime($dates[1]));

        if (isset($post['md_branch_id']))
            $md_branch_id = implode(", ", $post['md_branch_id']);

        if (isset($post['md_division_id']))
            $md_division_id = implode(", ", $post['md_division_id']);

        // Panggil class PHPExcel nya
        $excel = new PHPExcel();
        // Settingan awal file excel
        $excel->getProperties()->setCreator('Laporan Lembur Mingguan')
            ->setLastModifiedBy('Laporan Lembur Mingguan')
            ->setTitle("Laporan Lembur Mingguan")
            ->setSubject("Laporan Lembur Mingguan")
            ->setDescription("Laporan Lembur Mingguan")
            ->setKeywords("Laporan Lembur Mingguan");
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

        $excel->setActiveSheetIndex(0)->setCellValue('T1', $post['date']);
        $excel->getActiveSheet()->getStyle('T1')->getFont()->setBold(TRUE);
        $excel->getActiveSheet()->getStyle('T1')->getFont()->setSize(15);
        $excel->getActiveSheet()->getStyle('T1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

        $excel->setActiveSheetIndex(0)->setCellValue('A3', "LAPORAN LEMBUR MINGGUAN"); // Set kolom A1 dengan tulisan "LAPORAN ABSENSI HARIAN"
        $excel->getActiveSheet()->mergeCells('A3:T3');
        $excel->getActiveSheet()->getStyle('A3:T3')->getFont()->setBold(TRUE); // Set bold kolom A1
        $excel->getActiveSheet()->getStyle('A3:T3')->getFont()->setSize(15); // Set font size 15 untuk kolom A1
        $excel->getActiveSheet()->getStyle('A3:T3')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER); // Set text center untuk kolom A1
        // Buat header tabel nya pada baris ke 3
        $excel->setActiveSheetIndex(0)->setCellValue('A5', "Nik"); // Set kolom A3 dengan tulisan "Nik"
        $excel->getActiveSheet()->mergeCells('A5:A6');
        $excel->getActiveSheet()->getStyle('A5:A6')->applyFromArray($style_col); // Set Merge Cell
        $excel->setActiveSheetIndex(0)->setCellValue('B5', "Nama"); // Set kolom B3 dengan tulisan "Nama"
        $excel->getActiveSheet()->mergeCells('B5:B6');
        $excel->getActiveSheet()->getStyle('B5:B6')->applyFromArray($style_col); // Set Merge Cell
        $excel->setActiveSheetIndex(0)->setCellValue('C5', "Cabang"); // Set kolom C3 dengan tulisan "Cabang"
        $excel->getActiveSheet()->mergeCells('C5:C6');
        $excel->getActiveSheet()->getStyle('C5:C6')->applyFromArray($style_col); // Set Merge Cell
        $excel->setActiveSheetIndex(0)->setCellValue('D5', "Divisi"); // Set kolom D3 dengan tulisan "Divisi"
        $excel->getActiveSheet()->mergeCells('D5:D6');
        $excel->getActiveSheet()->getStyle('D5:D6')->applyFromArray($style_col); // Set Merge Cell

        $dateRange = getDatesFromRange($firstDate, $lastDate, [], 'Y-m-d', 'all');
        $saldoCol = 'E';
        $jumlahCol = 'F';

        /* 
        Set Up Header Table for Date, Start From here
        */
        foreach ($dateRange as $date) {
            $excel->setActiveSheetIndex(0)->setCellValue("{$saldoCol}5", date('d-M-Y', strtotime($date))); // Set kolom E5 dengan Tanggal Pertama
            $excel->setActiveSheetIndex(0)->setCellValue("{$saldoCol}6", 'saldo');
            $excel->setActiveSheetIndex(0)->setCellValue("{$jumlahCol}6", 'jumlah');
            $excel->getActiveSheet()->getStyle("{$saldoCol}6")->applyFromArray($style_col);
            $excel->getActiveSheet()->getStyle("{$jumlahCol}6")->applyFromArray($style_col);

            $excel->getActiveSheet()->mergeCells("{$saldoCol}5:{$jumlahCol}5"); // Set Merge Cell
            $excel->getActiveSheet()->getStyle("{$saldoCol}5:{$jumlahCol}5")->applyFromArray($style_col);

            $saldoCol++;
            $saldoCol++;
            $jumlahCol++;
            $jumlahCol++;
        }

        $excel->setActiveSheetIndex(0)->setCellValue("{$saldoCol}5", 'Total'); // Set kolom Total
        $excel->setActiveSheetIndex(0)->setCellValue("{$saldoCol}6", 'saldo');
        $excel->setActiveSheetIndex(0)->setCellValue("{$jumlahCol}6", 'jumlah');
        $excel->getActiveSheet()->getStyle("{$saldoCol}6")->applyFromArray($style_col);
        $excel->getActiveSheet()->getStyle("{$jumlahCol}6")->applyFromArray($style_col);

        $excel->getActiveSheet()->mergeCells("{$saldoCol}5:{$jumlahCol}5"); // Set Merge Cell
        $excel->getActiveSheet()->getStyle("{$saldoCol}5:{$jumlahCol}5")->applyFromArray($style_col);

        $whereClause = "trx_overtime.startdate BETWEEN '{$firstDate} 00:00:00' AND '{$lastDate} 23:59:59'";
        $whereClause .= " AND trx_overtime_detail.status = 'Y'";
        $whereClause .= " AND trx_overtime.docstatus = 'CO'";

        if (isset($md_branch_id)) {
            $whereClause .= " AND trx_overtime.md_branch_id IN ($md_branch_id)";
        }

        if (isset($md_division_id)) {
            $whereClause .= " AND trx_overtime.md_division_id IN ($md_division_id)";
        }


        $overtime = $mOvertime->getOvertimeDetail($whereClause)->getResult();

        $header = [];

        foreach ($overtime as $item) {
            $header[$item->nik][] = $item;
        }

        $numrow = 7; // Set baris pertama untuk isi tabel adalah baris ke 7
        $prevBranch = null;
        $prevDivision = null;

        // foreach ($header as $value) {
        $totalDivision = [];
        foreach ($header as $row) {

            if (($prevBranch && $row[0]->branch_name !== $prevBranch) || ($prevDivision && $row[0]->division_name !== $prevDivision)) {
                $excel->setActiveSheetIndex(0)->setCellValue("A{$numrow}", "Total Rupiah",);
                $excel->getActiveSheet()->mergeCells("A{$numrow}:$cellSaldo{$numrow}");
                $excel->getActiveSheet()->getStyle("A{$numrow}:$cellSaldo{$numrow}")->applyFromArray($styleCell);
                $excel->getActiveSheet()->getStyle("A{$numrow}:$cellSaldo{$numrow}")->getFont()->setBold(TRUE);
                $excel->getActiveSheet()->getStyle("A{$numrow}:$cellSaldo{$numrow}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

                $excel->setActiveSheetIndex(0)->setCellValue("$cellTotal{$numrow}", array_sum($totalDivision));
                $excel->getActiveSheet()->getStyle("$cellTotal{$numrow}")->applyFromArray($styleCell)->getNumberFormat()->setFormatCode("#,##0");
                $excel->getActiveSheet()->getStyle("$cellTotal{$numrow}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $excel->getActiveSheet()->getStyle("$cellTotal{$numrow}")->getFont()->setBold(TRUE);

                $numrow++;
                $numrow++;
                $totalDivision = [];
            }

            $excel->setActiveSheetIndex(0)->setCellValue('A' . $numrow, $row[0]->nik);
            $excel->setActiveSheetIndex(0)->setCellValue('B' . $numrow, $row[0]->employee_name);
            $excel->setActiveSheetIndex(0)->setCellValue('C' . $numrow, $row[0]->branch_name);
            $excel->setActiveSheetIndex(0)->setCellValue('D' . $numrow, $row[0]->division_name);

            // Apply style row yang telah kita buat tadi ke masing-masing baris (isi tabel)
            $excel->getActiveSheet()->getStyle('A' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('B' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('C' . $numrow)->applyFromArray($style_row);
            $excel->getActiveSheet()->getStyle('D' . $numrow)->applyFromArray($style_row);

            $cellSaldo = 'E';
            $cellTotal = 'F';

            $prevTotal = [];
            $prevSaldo = [];
            foreach ($dateRange as $date) {
                $tanggal = date('Y-m-d H:i:s', strtotime($date));
                $tglend =
                    date('Y-m-d 23:59:59', strtotime($date));

                $detail = $mOvertimeDetail->where(['md_employee_id' => $row[0]->md_employee_id, 'startdate >' => $tanggal, 'status' => 'Y', 'startdate <' => $tglend])->first();

                $styleCell = $style_row;

                $saldo = 0;
                $total = 0;

                if ($detail) {
                    $saldo = $detail->overtime_balance;
                    $total = $detail->total;
                }

                $excel->setActiveSheetIndex(0)->setCellValue($cellSaldo . $numrow, $saldo); // For Set Value Overtime Balance
                $excel->setActiveSheetIndex(0)->setCellValue($cellTotal . $numrow, $total); // For Set Value Total
                $excel->getActiveSheet()->getStyle($cellSaldo . $numrow)->applyFromArray($styleCell);
                $excel->getActiveSheet()->getStyle($cellTotal . $numrow)->applyFromArray($styleCell)->getNumberFormat()->setFormatCode("#,##0");

                $prevSaldo[] = $saldo;
                $prevTotal[] = $total;
                $totalDivision[] = $total;
                $cellSaldo++;
                $cellSaldo++;

                $cellTotal++;
                $cellTotal++;
            }

            $excel->setActiveSheetIndex(0)->setCellValue($cellSaldo . $numrow, array_sum($prevSaldo));
            $excel->getActiveSheet()->getStyle($cellSaldo . $numrow)->applyFromArray($styleCell);

            $excel->setActiveSheetIndex(0)->setCellValue($cellTotal . $numrow, array_sum($prevTotal));
            $excel->getActiveSheet()->getStyle($cellTotal . $numrow)->applyFromArray($styleCell)->getNumberFormat()->setFormatCode("#,##0");
            $excel->getActiveSheet()->getRowDimension($numrow)->setRowHeight(20);

            $numrow++; // Tambah 1 setiap kali looping

            $prevBranch = $row[0]->branch_name;
            $prevDivision = $row[0]->division_name;

            if ($row === end($header)) {
                $excel->setActiveSheetIndex(0)->setCellValue("A{$numrow}", "Total Rupiah",);
                $excel->getActiveSheet()->mergeCells("A{$numrow}:$cellSaldo{$numrow}");
                $excel->getActiveSheet()->getStyle("A{$numrow}:$cellSaldo{$numrow}")->applyFromArray($styleCell);
                $excel->getActiveSheet()->getStyle("A{$numrow}:$cellSaldo{$numrow}")->getFont()->setBold(TRUE);
                $excel->getActiveSheet()->getStyle("A{$numrow}:$cellSaldo{$numrow}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

                $excel->setActiveSheetIndex(0)->setCellValue("$cellTotal{$numrow}", array_sum($totalDivision));
                $excel->getActiveSheet()->getStyle("$cellTotal{$numrow}")->applyFromArray($styleCell)->getNumberFormat()->setFormatCode("#,##0");
                $excel->getActiveSheet()->getStyle("$cellTotal{$numrow}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $excel->getActiveSheet()->getStyle("$cellTotal{$numrow}")->getFont()->setBold(TRUE);
            }
        }
        // Set width kolom
        $excel->getActiveSheet()->getColumnDimension('A')->setWidth(10); // Set width kolom A
        $excel->getActiveSheet()->getColumnDimension('B')->setWidth(15); // Set width kolom B
        $excel->getActiveSheet()->getColumnDimension('C')->setWidth(25); // Set width kolom C
        $excel->getActiveSheet()->getColumnDimension('D')->setWidth(20); // Set width kolom D
        // Set orientasi kertas jadi LANDSCAPE
        $excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
        // Set judul file excel nya
        $excel->getActiveSheet(0)->setTitle("Laporan Lembur Mingguan");
        $excel->setActiveSheetIndex(0)->setShowGridlines(false);
        // Proses file excel
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Laporan Lembur Mingguan.xlsx"'); // Set nama file excel nya
        header('Cache-Control: max-age=0');
        $write = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $write->save('php://output');
        exit();
    }
}

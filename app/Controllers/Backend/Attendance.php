<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use App\Models\M_AbsentDetail;
use App\Models\M_Assignment;
use App\Models\M_AssignmentDate;
use App\Models\M_AssignmentDetail;
use App\Models\M_Attendance;
use App\Models\M_EmpBranch;
use App\Models\M_EmpDivision;
use App\Models\M_Employee;
use App\Models\M_WorkDetail;
use App\Models\M_NotificationText;
use App\Models\M_User;
use Config\Services;
use Html2Text\Html2Text;
use PHPExcel_Style_Alignment;
use PHPExcel_IOFactory;
use PHPExcel_Style_Border;
use PHPExcel;
use PHPExcel_Worksheet_PageSetup;

class Attendance extends BaseController
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

        $data = [
            'date_range' => $date . ' - ' . $date
        ];

        return $this->template->render('report/attendance/v_attendance', $data);
    }

    public function reportShowAll()
    {
        $post = $this->request->getVar();

        $recordTotal = 0;
        $recordsFiltered = 0;
        $data = [];

        if ($this->request->getMethod(true) === 'POST') {
            if (isset($post['form']) && $post['clear'] === 'false') {
                $table = "v_attendance";
                $select = $this->model->getSelect();
                $join = $this->model->getJoin();
                $order = $this->request->getPost('columns');
                $search = $this->request->getPost('search');
                $sort = ['v_attendance.date' => 'ASC', 'v_attendance.nik' => 'ASC'];
                $where = [];

                $number = $this->request->getPost('start');
                $list = array_unique($this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where), SORT_REGULAR);

                foreach ($list as $val) :
                    $row = [];

                    $number++;

                    $row[] = $number;
                    $row[] = $val->nik;
                    $row[] = $val->fullname;
                    $row[] = format_dmy($val->date, "-");
                    $row[] = $val->clock_in ?? format_time($val->clock_in);
                    $row[] = $val->clock_out ?? format_time($val->clock_out);
                    $data[] = $row;
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

    public function getClockInOut()
    {
        if ($this->request->isAJAX()) {
            $post = $this->request->getVar();

            try {
                $data = '';

                if ($post['typeform'] == 100008) {
                    $mAssignmentDate = new M_AssignmentDate($this->request);
                    $mAssignmentDetail = new M_AssignmentDetail($this->request);

                    $subDetail = $mAssignmentDate->find($post['id']);
                    $detail = $mAssignmentDetail->find($subDetail->{$mAssignmentDetail->primaryKey});

                    $att = $this->model->getAttendanceBranch([
                        'v_attendance_serialnumber.md_employee_id' => $detail->md_employee_id,
                        'v_attendance_serialnumber.date' => date("Y-m-d", strtotime($subDetail->date)),
                        'md_attendance_machines.md_branch_id' => $post['md_branch_id']
                    ])->getRow();

                    $data = [
                        'clock_in' => $att && $att->clock_in ? format_time($att->clock_in) : '',
                        'clock_out' => $att && $att->clock_out ? format_time($att->clock_out) : ''
                    ];
                } else {
                    $att = $this->model->getAttendance([
                        'v_attendance.nik'        => $post['nik'],
                        'v_attendance.date'       => date("Y-m-d", strtotime($post['startdate']))
                    ])->getRow();

                    if ($post['typeform'] == 100012 && $att) {
                        $data = format_time($att->clock_in);
                    } else if ($post['typeform'] == 100013 && $att) {
                        $data = format_time($att->clock_out);
                    }
                }

                $response['clock'] = $data;
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function toDoCheckAbsent()
    {
        $mEmployee = new M_Employee($this->request);
        $mNotifText = new M_NotificationText($this->request);
        $mUser = new M_User($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);
        $mAbsent = new M_Absent($this->request);
        $mAbsentDetail = new M_AbsentDetail($this->request);
        $cMessage = new Message();
        $cMail = new Mail();

        $today = date("Y-m-d");
        $yesterday = date("Y-m-d", strtotime("-1 day"));

        $dataNotifIn = $mNotifText->where('name', 'Belum Absen Masuk')->first();
        $dataNotifOut = $mNotifText->where('name', 'Belum Absen Pulang')->first();
        $employee = $mEmployee->where('isactive', 'Y')->findAll();

        foreach ($employee as $value) {
            $user = $mUser->where('md_employee_id', $value->md_employee_id)->first();

            if ($user) {
                //** This Section for checking Today Absent In */

                $day = strtoupper(formatDay_idn(date('w')));

                // TODO : Get Workday Employee
                $whereClause = "md_work_detail.isactive = 'Y'";
                $whereClause .= " AND md_employee_work.md_employee_id = {$value->md_employee_id}";
                $whereClause .= " AND md_employee_work.validfrom <= '{$today}'";
                $whereClause .= " AND md_employee_work.validto >= '{$today}'";
                $whereClause .= " AND md_day.name = '{$day}'";
                $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getRow();

                // TODO : Get Attendance In Today
                $whereClause = "v_attendance.md_employee_id = {$value->md_employee_id}";
                $whereClause .= " AND v_attendance.date = '{$today}'";
                $whereClause .= " AND v_attendance.clock_in is NOT NULL";
                $absentIn = $this->model->getAttendance($whereClause)->getRow();

                // TODO : Get Submission Today
                $whereClause = "v_realization.md_employee_id = {$value->md_employee_id}";
                $whereClause .= " AND v_realization.date = {$today}";
                $whereClause .= " AND v_realization.isagree = 'Y'";
                $whereClause .= " AND v_realization.submissiontype IN ('{$mAbsent->Pengajuan_sakit}', '{$mAbsent->Pengajuan_Cuti}', '{$mAbsent->Pengajuan_Ijin}', '{$mAbsent->Pengajuan_Ijin_Resmi}', '{$mAbsent->Pengajuan_Tugas_Kantor}')";
                $whereClause .= " AND v_realization.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')";
                $submission = $mAbsentDetail->getAllSubmission($whereClause)->getRow();

                if (!$absentIn && $workDetail && !$submission && $dataNotifIn) {
                    $text = $dataNotifIn->text . date('d F Y');

                    $cMessage->sendNotification($user->sys_user_id, $dataNotifIn->subject, $text);

                    if ($user->email) {
                        $text = new Html2Text($text);
                        $text = $text->getText();
                        $cMail->sendEmail($user->email, $dataNotifIn->subject, $text);
                    }
                }

                //** This Section for checking Yesterday Absent Out*/
                $day = strtoupper(formatDay_idn(date('w', strtotime($yesterday))));

                // TODO : Get Workday Employee
                $whereClause = "md_work_detail.isactive = 'Y'";
                $whereClause .= " AND md_employee_work.md_employee_id = {$value->md_employee_id}";
                $whereClause .= " AND md_employee_work.validfrom <= '{$yesterday}'";
                $whereClause .= " AND md_employee_work.validto >= '{$yesterday}'";
                $whereClause .= " AND md_day.name = '{$day}'";
                $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getRow();

                // TODO : Get Assignment Yesterday
                $whereClause = "v_realization.md_employee_id = {$value->md_employee_id}";
                $whereClause .= " AND v_realization.date = {$yesterday}";
                $whereClause .= " AND v_realization.isagree = 'Y'";
                $whereClause .= " AND v_realization.submissiontype IN ({$mAbsent->Pengajuan_Penugasan})";
                $whereClause .= " AND v_realization.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')";
                $assignment = $mAbsentDetail->getAllSubmission($whereClause)->getRow();

                // TODO : Get Attendance Out Yesterday
                $whereClause = "v_attendance.md_employee_id = {$value->md_employee_id} ";
                $whereClause .= " AND v_attendance.date = '{$yesterday}'";
                $whereClause .= " AND v_attendance.clock_out IS NOT NULL";
                $absentOut = $this->model->getAttendance($whereClause)->getRow();

                // TODO : Get Submission Forget Absent Leave Yesterday
                $whereClause = "v_realization.md_employee_id = {$value->md_employee_id}";
                $whereClause .= " AND v_realization.date = {$yesterday}";
                $whereClause .= " AND v_realization.isagree = 'Y'";
                $whereClause .= " AND v_realization.submissiontype IN ({$mAbsent->Pengajuan_Lupa_Absen_Pulang})";
                $whereClause .= " AND v_realization.docstatus IN ('{$this->DOCSTATUS_Completed}')";
                $forgotAbsentLeave = $mAbsentDetail->getAllSubmission($whereClause)->getRow();

                if (($workDetail || $assignment) && !$absentOut && !$forgotAbsentLeave && $dataNotifOut) {
                    $text = $dataNotifOut->text . date('d F Y', strtotime($yesterday));
                    $cMessage->sendNotification($user->sys_user_id, $dataNotifOut->subject, $text);

                    if ($user->email) {
                        $text = new Html2Text($text);
                        $text = $text->getText();
                        $cMail->sendEmail($user->email, $dataNotifOut->subject, $text);
                    }
                }
            }
        }
    }

    public function toDoSendAbsentSummary()
    {
        $mAssignment = new M_Assignment($this->request);
        $mEmployee = new M_Employee($this->request);
        $mNotifText = new M_NotificationText($this->request);
        $mUser = new M_User($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);
        $mAbsent = new M_Absent($this->request);
        $mAbsentDetail = new M_AbsentDetail($this->request);
        $mEmpBranch = new M_EmpBranch($this->request);
        $mEmpDivision = new M_EmpDivision($this->request);
        $cMail = new Mail();

        $today = date("Y-m-d");
        $storagePath = FCPATH . "/uploads/attsummary/";

        $manager = $mEmployee->where(['isactive' => 'Y', 'md_levelling_id' => 100003])->findAll();

        $dataNotif = $mNotifText->where('name', 'Summary Absent')->first();

        $seq = 1;
        foreach ($manager as $value) {
            $user = $mUser->where('md_employee_id', $value->md_employee_id)->first();
            if ($user && $user->email) {
                $isData = false;
                $filename = 'Laporan Karyawan Tidak Absen Masuk ' . date('d-m-Y') . ' 00' . $seq . '.xlsx';

                $excel = new PHPExcel();
                $excel->getProperties()->setCreator('sas')
                    ->setLastModifiedBy('sas')
                    ->setTitle("Laporan Absen Karyawan Summary")
                    ->setSubject("Laporan Absen Karyawan Summary")
                    ->setDescription("Laporan Absen Karyawan Summary")
                    ->setKeywords("Laporan Absen Karyawan Summary");

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

                $whereClause = "md_employee.isactive = 'Y'";
                $whereClause .= " AND md_employee.superior_id IN (select e.md_employee_id from md_employee e where e.superior_id in (select e.md_employee_id from md_employee e where e.superior_id = $value->md_employee_id))";
                $whereClause .= " OR md_employee.superior_id IN (SELECT e.md_employee_id FROM md_employee e WHERE e.superior_id = $value->md_employee_id)";
                $whereClause .= " OR md_employee.superior_id = $value->md_employee_id";
                $whereClause .= " AND md_employee.md_status_id NOT IN ({$this->Status_RESIGN}, {$this->Status_OUTSOURCING})";
                $employee = $mEmployee->getEmployee($whereClause);


                $excel->setActiveSheetIndex(0)->setCellValue('A1', date('d M Y'));
                $excel->setActiveSheetIndex(0)->setCellValue('A2', 'Alert karyawan Tidak Absen');
                $excel->getActiveSheet()->getStyle('A2')->getFont()->setBold(TRUE); // Set bold kolom A1
                $excel->getActiveSheet()->getStyle('A2')->getFont()->setSize(15); // Set font size 15 untuk kolom A1
                $excel->getActiveSheet()->getStyle('A2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER); // Set text center untuk kolom A1
                $excel->getActiveSheet()->mergeCells('A2:E2');

                $excel->setActiveSheetIndex(0)->setCellValue('A4', "No");
                $excel->getActiveSheet()->getStyle('A4')->applyFromArray($style_col);
                $excel->setActiveSheetIndex(0)->setCellValue('B4', "Nik");
                $excel->getActiveSheet()->getStyle('B4')->applyFromArray($style_col);
                $excel->setActiveSheetIndex(0)->setCellValue('C4', "Nama");
                $excel->getActiveSheet()->getStyle('C4')->applyFromArray($style_col);
                $excel->setActiveSheetIndex(0)->setCellValue('D4', "Cabang");
                $excel->getActiveSheet()->getStyle('D4')->applyFromArray($style_col);
                $excel->setActiveSheetIndex(0)->setCellValue('E4', "Divisi");
                $excel->getActiveSheet()->getStyle('E4')->applyFromArray($style_col);

                $row = 5;
                $number = 1;
                foreach ($employee as $val) {
                    $day = strtoupper(formatDay_idn(date('w')));
                    $branch = $mEmpBranch->getBranchDetail("md_employee_branch.md_employee_id = {$val->md_employee_id}")->getRow();
                    $division = $mEmpDivision->getDivisionDetail("md_employee_division.md_employee_id = {$val->md_employee_id}")->getRow();

                    // TODO : Get Workday Employee
                    $whereClause = "md_work_detail.isactive = 'Y'";
                    $whereClause .= " AND md_employee_work.md_employee_id = {$val->md_employee_id}";
                    $whereClause .= " AND md_employee_work.validfrom <= '{$today}'";
                    $whereClause .= " AND md_employee_work.validto >= '{$today}'";
                    $whereClause .= " AND md_day.name = '{$day}'";
                    $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getRow();

                    // TODO : Get Absent Clock In Today
                    $whereClause = " v_attendance_serialnumber.md_employee_id = {$val->md_employee_id}";
                    $whereClause .= " AND v_attendance_serialnumber.date = '{$today}'";
                    $whereClause .= " AND v_attendance_serialnumber.clock_in IS NOT NULL";
                    $whereClause .= " AND md_attendance_machines.md_branch_id = {$branch->md_branch_id}";
                    $absentIn = $this->model->getAttendanceBranch($whereClause)->getRow();

                    // TODO : Get Submission Today
                    $whereClause = "v_realization.md_employee_id = {$val->md_employee_id}";
                    $whereClause .= " AND v_realization.date = '{$today}'";
                    $whereClause .= " AND v_realization.isagree = 'Y'";
                    $whereClause .= " AND v_realization.submissiontype IN ('{$mAbsent->Pengajuan_sakit}', '{$mAbsent->Pengajuan_Cuti}', '{$mAbsent->Pengajuan_Ijin}', '{$mAbsent->Pengajuan_Ijin_Resmi}', '{$mAbsent->Pengajuan_Tugas_Kantor}')";
                    $whereClause .= " AND v_realization.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')";
                    $submission = $mAbsentDetail->getAllSubmission($whereClause)->getRow();

                    // TODO : Get Assignment Today
                    $whereClause = "trx_assignment_detail.md_employee_id = {$val->md_employee_id}";
                    $whereClause .= " AND trx_assignment_date.date = '{$today}'";
                    $whereClause .= " AND trx_assignment_date.isagree = 'Y'";
                    $whereClause .= " AND trx_assignment.submissiontype IN ({$mAbsent->Pengajuan_Penugasan})";
                    $whereClause .= " AND trx_assignment.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')";
                    $subAssign = $mAssignment->getDetailData($whereClause)->getRow();

                    $absentInBranch = null;

                    // TODO : get Absent In Based on Submission Assignment
                    if ($subAssign) {
                        $whereClause = " v_attendance_serialnumber.md_employee_id = {$val->md_employee_id}";
                        $whereClause .= " AND v_attendance_serialnumber.date = '{$today}'";
                        $whereClause .= " AND v_attendance_serialnumber.clock_in IS NOT NULL";
                        $whereClause .= " AND md_attendance_machines.md_branch_id = {$subAssign->branch_in}";
                        $absentInBranch = $this->model->getAttendanceBranch($whereClause)->getRow();
                    }

                    // TODO : If There no Clock In, Then Insert to Report
                    if ((!$absentIn && !$submission && $workDetail) && !$absentInBranch) {
                        $excel->setActiveSheetIndex(0)->setCellValue('A' . $row, $number);
                        $excel->getActiveSheet()->getStyle('A' . $row)->applyFromArray($style_row);
                        $excel->setActiveSheetIndex(0)->setCellValue('B' . $row, $val->nik);
                        $excel->getActiveSheet()->getStyle('B' . $row)->applyFromArray($style_row);
                        $excel->setActiveSheetIndex(0)->setCellValue('C' . $row, $val->fullname);
                        $excel->getActiveSheet()->getStyle('C' . $row)->applyFromArray($style_row);
                        $excel->setActiveSheetIndex(0)->setCellValue('D' . $row, $branch->branch_name);
                        $excel->getActiveSheet()->getStyle('D' . $row)->applyFromArray($style_row);
                        $excel->setActiveSheetIndex(0)->setCellValue('E' . $row, $division->division_name);
                        $excel->getActiveSheet()->getStyle('E' . $row)->applyFromArray($style_row);
                        $row++;
                        $number++;

                        $isData = true;
                    }
                }

                $excel->getActiveSheet()->getColumnDimension('A')->setWidth(3);
                $excel->getActiveSheet()->getColumnDimension('B')->setWidth(10); // Set width kolom A
                $excel->getActiveSheet()->getColumnDimension('C')->setWidth(30); // Set width kolom B
                $excel->getActiveSheet()->getColumnDimension('D')->setWidth(15); // Set width kolom C
                $excel->getActiveSheet()->getColumnDimension('E')->setWidth(15);

                $excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
                $excel->getActiveSheet(0)->setTitle("Laporan Absen Karyawan Summary");
                $excel->setActiveSheetIndex(0);

                if ($isData) {
                    $write = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
                    $filePath = $storagePath . $filename;

                    if (!is_dir($storagePath))
                        mkdir($storagePath, 0777, true);

                    // TODO : Save File and Send Email
                    $write->save($filePath);
                    $text = $dataNotif->text . date('d F Y');
                    $text = new Html2Text($text);
                    $text = $text->getText();
                    $cMail->sendEmail($user->email, $dataNotif->subject, $text, null, null, $filePath);

                    $seq++;
                }
            }
        }
    }

    public function toDoDeleteAttSummary()
    {
        $storagePath = FCPATH . "/uploads/attsummary";

        if (is_dir($storagePath))
            exec("rm -rf " . escapeshellarg($storagePath));
    }
}
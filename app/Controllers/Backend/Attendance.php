<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use App\Models\M_AbsentDetail;
use App\Models\M_Assignment;
use App\Models\M_AssignmentDate;
use App\Models\M_AssignmentDetail;
use App\Models\M_Attendance;
use App\Models\M_Configuration;
use App\Models\M_EmpBranch;
use App\Models\M_EmpDivision;
use App\Models\M_Employee;
use App\Models\M_Holiday;
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

                // TODO : Get Employee Access
                $empList = $this->access->getEmployeeData();
                $where['v_attendance.md_employee_id'] = ['value' => $empList];

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

                    $att = $this->model->getAttBranch([
                        'v_attendance_branch.md_employee_id' => $detail->md_employee_id,
                        'v_attendance_branch.date' => date("Y-m-d", strtotime($subDetail->date)),
                        'v_attendance_branch.md_branch_id' => $post['md_branch_id']
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
        $mHoliday = new M_Holiday($this->request);
        $mConfig = new M_Configuration($this->request);
        $mAssignment = new M_Assignment($this->request);
        $mEmpBranch = new M_EmpBranch($this->request);
        $cMessage = new Message();
        $cTelegram = new Telegram();

        $holiday = $mHoliday->getHolidayDate();
        $today = date("Y-m-d");
        $yesterday = addBusinessDays($today, 1, $holiday, true);

        // TODO : Set Master Data Notification
        $dataNotifIn = $mNotifText->where('name', 'Belum Absen Masuk')->first();
        $subjectIn = $dataNotifIn->getSubject();
        $messageIn = str_replace(['(Var1)'], [$today], $dataNotifIn->getText());
        $dataNotifOut = $mNotifText->where('name', 'Belum Absen Pulang')->first();
        $subjectOut = $dataNotifOut->getSubject();
        $messageOut = str_replace(['(Var1)'], [$yesterday], $dataNotifOut->getText());
        $employee = $mEmployee->where('isactive', 'Y')->whereIn('md_status_id', [100001, 100002])->whereNotIn('md_levelling_id', [100001])->findAll();

        $configMNSOD = $mConfig->where('name', 'MANAGER_NO_NEED_SPECIAL_OFFICE_DUTIES')->first();

        $configMNSOD = $configMNSOD->value == 'Y' ? true : false;
        $lvlManager = 100003;

        foreach ($employee as $value) {
            $empBranch = $mEmpBranch->where('md_employee_id', $value->md_employee_id)->findAll();
            $user = $mUser->where(['md_employee_id' => $value->md_employee_id, 'isactive' => 'Y'])->first();

            if (empty($empBranch)) {
                continue;
            }

            $empBranch = implode(", ", array_column($empBranch, 'md_branch_id'));

            //** This Section for checking Today Absent In */

            $day = strtoupper(formatDay_idn(date('w')));

            // TODO : Get Workday Employee
            $whereClause = "md_work_detail.isactive = 'Y'";
            $whereClause .= " AND md_employee_work.md_employee_id = {$value->md_employee_id}";
            $whereClause .= " AND md_employee_work.validfrom <= '{$today}'";
            $whereClause .= " AND md_employee_work.validto >= '{$today}'";
            $whereClause .= " AND md_day.name = '{$day}'";
            $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getRow();

            // TODO : Get Submission Assignment
            $whereClause = "DATE(trx_assignment_date.date) = '{$today}'
                    AND trx_assignment.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')
                    AND trx_assignment_detail.md_employee_id = {$value->md_employee_id}
                    AND trx_assignment_date.isagree IN ('{$this->LINESTATUS_Approval}', '{$this->LINESTATUS_Realisasi_HRD}}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Disetujui}')
                    AND trx_assignment.submissiontype = {$mAssignment->Pengajuan_Penugasan}";

            $tugasKunjungan = $mAssignment->getDetailData($whereClause)->getRow();

            // TODO : Get Attendance In Today
            if ($configMNSOD && $employee == $lvlManager) {
                $whereClause = "v_attendance.md_employee_id = {$value->md_employee_id}";
                $whereClause .= " AND v_attendance.date = '{$today}'";
                $whereClause .= " AND v_attendance.clock_in is NOT NULL";
                $absentIn = $this->model->getAttendance($whereClause)->getRow();
            } else {
                $whereClause = "v_attendance_branch.md_employee_id = {$value->md_employee_id}";
                $whereClause .= " AND v_attendance_branch.date = '{$today}'";
                $whereClause .= " AND v_attendance_branch.clock_in is NOT NULL";

                if ($tugasKunjungan) {
                    $whereClause .= " AND v_attendance_branch.md_branch_id = {$tugasKunjungan->branch_in}";
                } else {
                    $whereClause .= " AND v_attendance_branch.md_branch_id IN ({$empBranch})";
                }

                $absentIn = $this->model->getAttBranch($whereClause)->getRow();
            }

            // TODO : Get Submission Today
            $whereClause = "v_realization.md_employee_id = {$value->md_employee_id}";
            $whereClause .= " AND v_realization.date = '{$today}'";
            $whereClause .= " AND v_realization.isagree IN ('{$this->LINESTATUS_Realisasi_HRD}}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Disetujui}')";
            $whereClause .= " AND v_realization.submissiontype IN ('{$mAbsent->Pengajuan_sakit}', '{$mAbsent->Pengajuan_Cuti}', '{$mAbsent->Pengajuan_Ijin}', '{$mAbsent->Pengajuan_Ijin_Resmi}', '{$mAbsent->Pengajuan_Tugas_Kantor}', '{$mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari}')";
            $whereClause .= " AND v_realization.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')";
            $submission = $mAbsentDetail->getAllSubmission($whereClause)->getRow();

            if (!$absentIn && ($workDetail || $tugasKunjungan) && !$submission && !in_array($today, $holiday) && $dataNotifIn) {
                if ($user) {
                    $cMessage->sendInformation($user, $subjectIn, $messageIn, 'HARMONY SAS', null, null, true, true, true);
                } else if (!empty($value->telegram_id)) {
                    $cTelegram->sendMessage($value->telegram_id, $messageIn);
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

            // TODO : Get Submission Assignment Yesterday
            $whereClause = "DATE(trx_assignment_date.date) = '{$yesterday}'
                    AND trx_assignment.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')
                    AND trx_assignment_detail.md_employee_id = {$value->md_employee_id}
                    AND trx_assignment_date.isagree IN ('{$this->LINESTATUS_Approval}', '{$this->LINESTATUS_Realisasi_HRD}}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Disetujui}')
                    AND trx_assignment.submissiontype = {$mAssignment->Pengajuan_Penugasan}";

            $tugasKunjungan = $mAssignment->getDetailData($whereClause)->getRow();

            // TODO : Get Submission Yesterday
            $whereClause = "v_realization.md_employee_id = {$value->md_employee_id}";
            $whereClause .= " AND v_realization.date = '{$yesterday}'";
            $whereClause .= " AND v_realization.isagree IN ('{$this->LINESTATUS_Realisasi_HRD}}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Disetujui}')";
            $whereClause .= " AND v_realization.submissiontype IN ('{$mAbsent->Pengajuan_sakit}', '{$mAbsent->Pengajuan_Cuti}', '{$mAbsent->Pengajuan_Ijin}', '{$mAbsent->Pengajuan_Ijin_Resmi}', '{$mAbsent->Pengajuan_Tugas_Kantor}', '{$mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari}')";
            $whereClause .= " AND v_realization.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')";
            $submission = $mAbsentDetail->getAllSubmission($whereClause)->getRow();

            // TODO : Get Attendance Out Yesterday
            if ($configMNSOD && $employee == $lvlManager) {
                $whereClause = "v_attendance.md_employee_id = {$value->md_employee_id}";
                $whereClause .= " AND v_attendance.date = '{$yesterday}'";
                $whereClause .= " AND v_attendance.clock_out is NOT NULL";
                $absentOut = $this->model->getAttendance($whereClause)->getRow();
            } else {
                $whereClause = "v_attendance_branch.md_employee_id = {$value->md_employee_id}";
                $whereClause .= " AND v_attendance_branch.date = '{$yesterday}'";
                $whereClause .= " AND v_attendance_branch.clock_out is NOT NULL";

                if ($tugasKunjungan) {
                    $whereClause .= " AND v_attendance_branch.md_branch_id = {$tugasKunjungan->branch_out}";
                } else {
                    $whereClause .= " AND v_attendance_branch.md_branch_id IN ({$empBranch})";
                }

                $absentOut = $this->model->getAttBranch($whereClause)->getRow();
            }

            // TODO : Get Submission Forget Absent Leave Yesterday
            $whereClause = "v_realization.md_employee_id = {$value->md_employee_id}";
            $whereClause .= " AND v_realization.date = '{$yesterday}'";
            $whereClause .= " AND v_realization.isagree IN ('{$this->LINESTATUS_Realisasi_HRD}}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Disetujui}')";
            $whereClause .= " AND v_realization.submissiontype IN ({$mAbsent->Pengajuan_Lupa_Absen_Pulang}, {$mAbsent->Pengajuan_Pulang_Cepat})";
            $whereClause .= " AND v_realization.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')";
            $forgotAbsentLeave = $mAbsentDetail->getAllSubmission($whereClause)->getRow();

            if ($workDetail && !$absentOut && !$forgotAbsentLeave && !$submission && $dataNotifOut) {
                if ($user) {
                    $cMessage->sendInformation($user, $subjectOut, $messageOut, 'HARMONY SAS', null, null, true, true, true);
                } else if (!empty($value->telegram_id)) {
                    $cTelegram->sendMessage($value->telegram_id, $messageOut);
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
        $mHoliday = new M_Holiday($this->request);
        $cMessage = new Message();

        $today = date("Y-m-d");
        $storagePath = FCPATH . "/uploads/attsummary/";
        $holiday = $mHoliday->getHolidayDate();

        if (in_array($today, $holiday))
            return;

        $manager = $mEmployee->where(['isactive' => 'Y', 'md_levelling_id' => 100003])->whereIn('md_status_id', [100001, 100002])->findAll();

        $dataNotif = $mNotifText->where('name', 'Summary Absent')->first();
        $message = str_replace(['(Var1)'], [$today], $dataNotif->getText());
        $subject = $dataNotif->getSubject();

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
                $whereClause .= " AND (md_employee.superior_id IN (select e.md_employee_id from md_employee e where e.superior_id in (select e.md_employee_id from md_employee e where e.superior_id = $value->md_employee_id))";
                $whereClause .= " OR md_employee.superior_id IN (SELECT e.md_employee_id FROM md_employee e WHERE e.superior_id = $value->md_employee_id)";
                $whereClause .= " OR md_employee.superior_id = $value->md_employee_id)";
                $whereClause .= " AND md_employee.md_status_id IN ({$this->Status_PERMANENT}, {$this->Status_PROBATION})";
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
                    if (empty($branch) || empty($division)) {
                        continue;
                    }

                    // TODO : Get Workday Employee
                    $whereClause = "md_work_detail.isactive = 'Y'";
                    $whereClause .= " AND md_employee_work.md_employee_id = {$val->md_employee_id}";
                    $whereClause .= " AND md_employee_work.validfrom <= '{$today}'";
                    $whereClause .= " AND md_employee_work.validto >= '{$today}'";
                    $whereClause .= " AND md_day.name = '{$day}'";
                    $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getRow();

                    // TODO : Get Submission Assignment
                    $whereClause = "DATE(trx_assignment_date.date) = '{$today}'
                    AND trx_assignment.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')
                    AND trx_assignment_detail.md_employee_id = {$val->md_employee_id}
                    AND trx_assignment_date.isagree IN ('{$this->LINESTATUS_Approval}', '{$this->LINESTATUS_Realisasi_HRD}}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Disetujui}')
                    AND trx_assignment.submissiontype = {$mAssignment->Pengajuan_Penugasan}";

                    $tugasKunjungan = $mAssignment->getDetailData($whereClause)->getRow();

                    // TODO : Get Absent Clock In Today
                    $whereClause = " v_attendance_branch.md_employee_id = {$val->md_employee_id}";
                    $whereClause .= " AND v_attendance_branch.date = '{$today}'";
                    $whereClause .= " AND v_attendance_branch.clock_in IS NOT NULL";

                    if ($tugasKunjungan) {
                        $whereClause .= " AND v_attendance_branch.md_branch_id = {$tugasKunjungan->branch_in}";
                    } else {
                        $whereClause .= " AND v_attendance_branch.md_branch_id = {$branch->md_branch_id}";
                    }

                    $absentIn = $this->model->getAttBranch($whereClause)->getRow();

                    // TODO : Get Submission Today
                    $whereClause = "v_realization.md_employee_id = {$val->md_employee_id}";
                    $whereClause .= " AND v_realization.date = '{$today}'";
                    $whereClause .= " AND v_realization.isagree IN ('{$this->LINESTATUS_Approval}', '{$this->LINESTATUS_Realisasi_HRD}}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Disetujui}')";
                    $whereClause .= " AND v_realization.submissiontype IN ('{$mAbsent->Pengajuan_sakit}', '{$mAbsent->Pengajuan_Cuti}', '{$mAbsent->Pengajuan_Ijin}', '{$mAbsent->Pengajuan_Ijin_Resmi}', '{$mAbsent->Pengajuan_Tugas_Kantor}', '{$mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari}')";
                    $whereClause .= " AND v_realization.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')";
                    $submission = $mAbsentDetail->getAllSubmission($whereClause)->getRow();


                    // TODO : If There no Clock In, Then Insert to Report
                    if ((!$absentIn && !$submission && $workDetail)) {
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
                    $cMessage->sendInformation($user, $subject, $message, null, $filePath, null, true, false, false);

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

<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use App\Models\M_AbsentDetail;
use App\Models\M_Assignment;
use App\Models\M_AssignmentDate;
use App\Models\M_AssignmentDetail;
use App\Models\M_Attendance;
use App\Models\M_Employee;
use App\Models\M_WorkDetail;
use App\Models\M_NotificationText;
use App\Models\M_User;
use Config\Services;
use Html2Text\Html2Text;

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
                $yesterday = date("Y-m-d", strtotime("-1 day"));
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
}

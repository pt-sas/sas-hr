<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use App\Models\M_AbsentDetail;
use App\Models\M_Attendance;
use App\Models\M_Overtime;
use App\Models\M_OvertimeDetail;
use App\Models\M_Rule;
use App\Models\M_RuleDetail;
use App\Models\M_EmpWorkDay;
use App\Models\M_WorkDetail;
use App\Models\M_AccessMenu;
use App\Models\M_Assignment;
use App\Models\M_AssignmentDate;
use App\Models\M_ChangeLog;
use App\Models\M_Configuration;
use App\Models\M_Employee;
use App\Models\M_Holiday;
use App\Models\M_LeaveBalance;
use App\Models\M_NotificationText;
use App\Models\M_SubmissionCancel;
use App\Models\M_SubmissionCancelDetail;
use App\Models\M_User;
use App\Models\M_UserRole;
use App\Models\M_Year;
use Html2Text\Html2Text;
use Config\Services;

class Realization extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Absent($this->request);
    }

    public function index()
    {
        $start_date = format_dmy(date('Y-m-d', strtotime('- 1 days')), "-");
        $end_date = format_dmy(date('Y-m-d'), "-");

        $data = [
            'date_range'            => $start_date . ' - ' . $end_date,
            'toolbarRealization'    => $this->template->buttonExport()
        ];

        return $this->template->render('transaction/realization/v_realization', $data);
    }

    public function indexOvertime()
    {
        $start_date = format_dmy(date('Y-m-d', strtotime('- 1 days')), "-");
        $end_date = format_dmy(date('Y-m-d'), "-");

        $data = [
            'date_range'            => $start_date . ' - ' . $end_date,
            'toolbarRealization'    => $this->template->toolbarButtonProcess()
        ];

        return $this->template->render('transaction/overtimerealization/v_overtime_realization', $data);
    }

    public function indexAttendance()
    {
        $start_date = format_dmy(date('Y-m-d', strtotime('- 1 days')), "-");
        $end_date = format_dmy(date('Y-m-d'), "-");

        $data = [
            'date_range'            => $start_date . ' - ' . $end_date,
            'toolbarRealization'    => $this->template->toolbarButtonProcess()
        ];

        return $this->template->render('transaction/attendancerealization/v_realization', $data);
    }

    public function showAll()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $mSubCancelDetail = new M_SubmissionCancelDetail($this->request);
            $table = 'v_realization_new';
            $select = '*';
            $join = [];
            $order = [
                '', // Number
                '', // submissiondate
                'v_realization_new.realization_hrd',
                'v_realization_new.doctype',
                'v_realization_new.branch',
                'v_realization_new.division',
                'v_realization_new.employee_fullname',
                '',
                'v_realization_new.reason'
            ];
            $search = $this->request->getPost('search');
            $sort = ['v_realization_new.date' => 'ASC', 'v_realization_new.employee_fullname' => 'ASC'];

            $formType = [
                $this->model->Pengajuan_Lupa_Absen_Masuk,
                $this->model->Pengajuan_Lupa_Absen_Pulang,
                $this->model->Pengajuan_Datang_Terlambat,
                $this->model->Pengajuan_Pulang_Cepat,
            ];

            $where = [
                "docstatus = '{$this->DOCSTATUS_Inprogress}'
                AND isagree = '{$this->LINESTATUS_Realisasi_HRD}' 
                AND submissiontype NOT IN (" . implode(",", $formType) . ")"
            ];

            $data = [];

            $fieldChk = new \App\Entities\Table();
            $fieldChk->setName("ischecked");
            $fieldChk->setType("checkbox");
            $fieldChk->setClass("check-realize");

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->id;

                if ($value->table == "trx_absent") {
                    $tableline = "trx_absent_detail";
                } else {
                    $tableline = "trx_assignment_date";
                }

                $trxCancel = $mSubCancelDetail->where(['reference_id' => $ID, 'ref_table' => $tableline])
                    ->whereIn('isagree', [$this->LINESTATUS_Approval, $this->LINESTATUS_Disetujui, $this->LINESTATUS_Realisasi_Atasan, "{$this->LINESTATUS_Realisasi_HRD}"])->first();

                $number++;

                $reason = $value->reason;
                $dateFormat = format_dmy($value->realization_hrd, '-');

                if ($value->comment)
                    $reason .= " | <small class='text-danger'>{$value->comment}</small>";

                $row[] = !$trxCancel ? $number : "<small class='text-danger'>{$number}</small>";
                $row[] = format_dmy($value->date, '-') . " | " . formatDay_idn(date('w', strtotime($value->date)));
                $row[] = !$trxCancel ? $dateFormat : "<small class='text-danger'>{$dateFormat}</small>";
                $row[] = !$trxCancel ? $value->doctype : "<small class='text-danger'>{$value->doctype}</small>";
                $row[] = !$trxCancel ? $value->branch : "<small class='text-danger'>{$value->branch}</small>";
                $row[] = !$trxCancel ? $value->division : "<small class='text-danger'>{$value->division}</small>";
                $row[] = !$trxCancel ? $value->employee_fullname : "<small class='text-danger'>{$value->employee_fullname}</small>";
                $row[] = viewImage($value->header_id, $value->image);
                $row[] = !$trxCancel ? $reason : "<small class='text-danger'>{$reason}</small>";
                $row[] = !$trxCancel ? $this->template->tableButtonProcess($ID) : null;
                $data[] = $row;
            endforeach;

            $result = [
                'draw'              => $this->request->getPost('draw'),
                'recordsTotal'      => $this->datatable->countAll($table, $select, $order, $sort, $search, $join, $where),
                'recordsFiltered'   => $this->datatable->countFiltered($table, $select, $order, $sort, $search, $join, $where),
                'data'              => $data
            ];

            return $this->response->setJSON($result);
        }
    }

    public function showAllOvertime()
    {
        $mAttendance = new M_Attendance($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $table = 'v_realization_overtime';
            $select = '*';
            $join = [];
            $order = [
                '',
                '',
                'documentno',
                'employee_name',
                'branch_name',
                'division_name',
                'startdate_line',
                'enddate_line'
            ];
            $search = $this->request->getPost('search');
            $sort = ['documentno' => 'ASC'];

            $where['docstatus'] = $this->DOCSTATUS_Inprogress;
            $where['md_employee_id'] = ['value' => $this->access->getEmployeeData(false)];

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];

                $startDateLine = date('Y-m-d', strtotime($value->startdate_line));

                $whereClause = "v_attendance.md_employee_id = {$value->md_employee_id}";
                $whereClause .= " AND v_attendance.date = '{$startDateLine}'";
                $whereClause .= " AND v_attendance.clock_out != ''";
                $attendance = $mAttendance->getAttendance($whereClause)->getRow();

                $ID = $value->trx_overtime_detail_id;

                $number++;

                $row[] = $number;
                $row[] = '';
                $row[] = $value->documentno;
                $row[] = $value->employee_name;
                $row[] = $value->branch_name;
                $row[] = $value->division_name;
                $row[] = format_dmy($value->startdate_line, '-');
                $row[] = format_dmy($value->enddate_line, '-');
                $row[] = format_time($value->startdate_line);
                $row[] = format_time($value->enddate_line);
                $row[] = $attendance ? format_dmy($attendance->date, '-') : '';
                $row[] = $attendance ? format_time($attendance->clock_out) : '';
                $row[] = $this->template->tableButtonProcess($ID);
                $data[] = $row;
            endforeach;

            $result = [
                'draw'              => $this->request->getPost('draw'),
                'recordsTotal'      => $this->datatable->countAll($table, $select, $order, $sort, $search, $join, $where),
                'recordsFiltered'   => $this->datatable->countFiltered($table, $select, $order, $sort, $search, $join, $where),
                'data'              => $data
            ];

            return $this->response->setJSON($result);
        }
    }

    public function showAllAttendance()
    {
        $mAttendance = new M_Attendance($this->request);
        $mAbsent = new M_Absent($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $table = 'v_realization_new';
            $select = '*';
            $join = [];
            $order = [
                '', // Number
                '',
                'v_realization_new.realization_mgr',
                '',
                'v_realization_new.documentno',
                'v_realization_new.doctype',
                'v_realization_new.branch',
                'v_realization_new.division',
                'v_realization_new.employee_fullname',
                '',
                '',
                'v_realization_new.reason'
            ];
            $search = $this->request->getPost('search');
            $sort = ['v_realization_new.date' => 'ASC', 'v_realization_new.employee_fullname' => 'ASC'];

            $where['docstatus'] = $this->DOCSTATUS_Inprogress;
            $where['isagree'] = 'M';

            $where['md_employee_id'] = ['value' => $this->access->getEmployeeData(false, true)];

            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            $number = $this->request->getPost('start');
            $data = [];
            foreach ($list as $value) :
                $row = [];
                $ID = $value->id;

                // $tanggal = '';
                // $clock = '';

                if ($value->submissiontype == $mAbsent->Pengajuan_Lupa_Absen_Pulang || $value->submissiontype == $mAbsent->Pengajuan_Lupa_Absen_Masuk)
                    $clock = format_time($value->date);

                // $startDate = date('Y-m-d', strtotime($value->date));

                // $whereClause = "v_attendance.md_employee_id = {$value->md_employee_id}";
                // $whereClause .= " AND v_attendance.date = '{$startDate}'";

                // if ($value->submissiontype == $mAbsent->Pengajuan_Pulang_Cepat)
                //     $whereClause .= " AND v_attendance.clock_out != ''";
                // else if ($value->submissiontype == $mAbsent->Pengajuan_Datang_Terlambat)
                //     $whereClause .= " AND v_attendance.clock_in != ''";

                // $attendance = $mAttendance->getAttendance($whereClause)->getRow();

                // if ($attendance && $value->submissiontype == $mAbsent->Pengajuan_Pulang_Cepat) {
                //     $tanggal = format_dmy($attendance->date, '-');
                //     $absent = format_time($attendance->clock_out);
                //     $clock = format_time($value->date);
                // } else if ($attendance && $value->submissiontype == $mAbsent->Pengajuan_Datang_Terlambat) {
                //     $tanggal = format_dmy($attendance->date, '-');
                //     $absent = format_time($attendance->clock_in);
                //     $clock = format_time($value->date);
                // }

                $number++;

                $row[] = $number;
                $row[] = format_dmy($value->date, '-') . " | " . formatDay_idn(date('w', strtotime($value->date)));
                $row[] = format_dmy($value->realization_mgr, '-');
                $row[] = $clock ?? '';
                $row[] = $value->documentno;
                $row[] = $value->doctype;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = $value->employee_fullname;
                // $row[] = $tanggal ?? '';
                // $row[] = $absent ?? '';
                $row[] = viewImage($value->header_id, $value->image);
                $row[] = $value->reason;
                $row[] = $this->template->tableButtonProcess($ID);
                $data[] = $row;
            endforeach;

            $result = [
                'draw'              => $this->request->getPost('draw'),
                'recordsTotal'      => $this->datatable->countAll($table, $select, $order, $sort, $search, $join, $where),
                'recordsFiltered'   => $this->datatable->countFiltered($table, $select, $order, $sort, $search, $join, $where),
                'data'              => $data
            ];

            return $this->response->setJSON($result);
        }
    }

    public function create()
    {
        $mAttendance = new M_Attendance($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();
            $mAbsentDetail = new M_AbsentDetail($this->request);
            $changeLog = new M_ChangeLog($this->request);
            $mEmployee = new M_Employee($this->request);
            $mUser = new M_User($this->request);
            $mNotifText = new M_NotificationText($this->request);
            $mLeaveBalance = new M_LeaveBalance($this->request);
            $mYear = new M_Year($this->request);
            $cMessage = new Message();
            $cTelegram = new Telegram();

            $post['submissiondate'] = trim(preg_replace('/\|.*/', '', $post['submissiondate']));
            $isAgree = $post['isagree'];
            $submissionDate = date('Y-m-d', strtotime($post['submissiondate']));
            $today = date('Y-m-d');
            $todayTime = date('Y-m-d H:i:s');

            $submissionForm = $post['submissionform'];

            if ($submissionForm == "Penugasan") {
                $table = "trx_assignment";
            } else if ($submissionForm == "Pembatalan") {
                $table = "trx_submission_cancel";
            } else {
                $table = "trx_absent";
            }

            if ($isAgree == $this->LINESTATUS_Disetujui) {
                $whereClause = "v_realization_new.id = {$post['id']}";
            } else {
                $whereClause = "v_realization_new.id = {$post['foreignkey']}";
            }

            $whereClause .= " AND v_realization_new.table = '{$table}'";
            $trx = $mAbsentDetail->getRealization($whereClause)->getRow();
            $realizeDate = date('Y-m-d', strtotime($trx->realization_hrd));

            try {
                if (
                    $isAgree == $this->LINESTATUS_Disetujui && $submissionForm == "Penugasan" ? !$this->validation->run($post, 'realisasi_agree_penugasan') :
                    !$this->validation->run($post, 'realisasi_agree')
                ) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else if (!$this->validation->run($post, 'realisasi_not_agree') && $isAgree == $this->LINESTATUS_Ditolak) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else if ($today < $realizeDate) {
                    $response = message('success', false, 'tanggal realisasi belum terpenuhi');
                } else {
                    $employee = $mEmployee->find($trx->md_employee_id);

                    if ($table == "trx_assignment") {
                        $model = new M_Assignment($this->request);
                        $this->model = new M_AssignmentDate($this->request);
                        $this->entity = new \App\Entities\AssignmentDate();
                    } else if ($table == "trx_submission_cancel") {
                        $model = new M_SubmissionCancel($this->request);
                        $this->model = new M_SubmissionCancelDetail($this->request);
                        $this->entity = new \App\Entities\SubmissionCancelDetail();
                    } else {
                        $model = new M_Absent($this->request);
                        $this->model = new M_AbsentDetail($this->request);
                        $this->entity = new \App\Entities\AbsentDetail();
                    }

                    if ($isAgree == $this->LINESTATUS_Disetujui) {
                        // TODO : Set Notification
                        $dataNotif = $mNotifText->where('name', 'Realisasi Disetujui HRD')->first();
                        $subject = $dataNotif->getSubject();
                        $message = str_replace(['(Var1)', '(Var2)'], [$trx->documentno, $submissionDate], $dataNotif->getText());

                        $this->entity->isagree = $this->LINESTATUS_Disetujui;
                        $this->entity->{$this->model->primaryKey} = $post['id'];
                        $this->entity->realization_date_hrd = date('Y-m-d H:i:s');
                        $this->entity->realization_by_hrd = $this->access->getSessionUser();


                        if ($submissionForm == "Penugasan") {
                            $this->entity->realization_in = date('Y-m-d', strtotime($post['submissiondate'])) . " " . $post['starttime_att'];
                            $this->entity->realization_out = date('Y-m-d', strtotime($post['submissiondate'])) . " " . $post['endtime_att'];

                            if (empty($post['starttime_att']) || empty($post['endtime_att'])) {
                                $clock_in = null;
                                $clock_out = null;

                                if (empty($post['starttime_att'])) {
                                    $whereIn = " v_attendance_serialnumber.md_employee_id = {$trx->md_employee_id}";
                                    $whereIn .= " AND v_attendance_serialnumber.date = '{$submissionDate}'";
                                    $whereIn .= " AND v_attendance_serialnumber.clock_in != ''";
                                    $whereIn .= " AND md_attendance_machines.md_branch_id != {$post['branch_in']}";
                                    $clock_in = $mAttendance->getAttendanceBranch($whereIn)->getRow();
                                }

                                if (empty($post['endtime_att'])) {
                                    $whereOut = " v_attendance_serialnumber.md_employee_id = {$trx->md_employee_id}";
                                    $whereOut .= " AND v_attendance_serialnumber.date = '{$submissionDate}'";
                                    $whereOut .= " AND v_attendance_serialnumber.clock_out != ''";
                                    $whereOut .= " AND md_attendance_machines.md_branch_id != {$post['branch_out']}";
                                    $clock_out = $mAttendance->getAttendanceBranch($whereOut)->getRow();
                                }

                                if (empty($clock_in) && empty($clock_out)) {
                                    $this->entity->instruction_in = 'N';
                                    $this->entity->instruction_out = 'N';
                                } else if (empty($clock_in)) {
                                    $this->entity->instruction_in = 'N';
                                    $this->entity->instruction_out = 'Y';
                                } else if (empty($clock_out)) {
                                    $this->entity->instruction_in = 'Y';
                                    $this->entity->instruction_out = 'N';
                                }

                                // if (empty($post['starttime_att']) && !isset($clock_in)) {
                                //     $response = message('success', false, "Tidak ada absensi masuk di cabang lain");
                                // } else if (empty($post['endtime_att']) && !isset($clock_out)) {
                                //     $response = message('success', false, "Tidak ada absensi pulang di cabang lain");
                                // } else {
                                $response = $this->save();
                                // }
                            } else {
                                $this->entity->instruction_in = 'Y';
                                $this->entity->instruction_out = 'Y';
                                $response = $this->save();
                            }
                        } else {
                            // TODO : When There's no Leave balance available, then return message no leave balance available
                            if ($submissionForm == "Cuti") {
                                $balance = $mLeaveBalance->where([
                                    'year'              => date("Y", strtotime($submissionDate)),
                                    'md_employee_id'    => $trx->md_employee_id
                                ])->first();

                                if (!$balance || ($balance && ($balance->balance_amount <= 0
                                    && ($balance->carried_over_amount <= 0
                                        || ($balance->carried_over_amount > 0 && $submissionDate > date('Y-m-d', strtotime($balance->carry_over_expiry_date))))
                                )))
                                    return $this->response->setJSON(message('success', false, 'Karyawan tidak memiliki Saldo Cuti'));
                            }

                            $response = $this->save();
                        }
                    } else {
                        // TODO : Set Notification
                        $dataNotif = $mNotifText->where('name', 'Realisasi Tidak Disetujui HRD')->first();
                        $subject = $dataNotif->getSubject();
                        $message = str_replace(['(Var1)', '(Var2)'], [$trx->documentno, $submissionDate], $dataNotif->getText());

                        if ($post['submissiontype'] !== 'tidak setuju') {


                            $this->model = new M_Absent($this->request);
                            $this->entity = new \App\Entities\Absent();

                            $isAssignment = $table == "trx_assignment" ? true : false;

                            /**
                             * Insert Pengajuan baru
                             */

                            $necessaryMap = [
                                'ijin' => ['IJ', $this->model->Pengajuan_Ijin],
                                'alpa' => ['AL', $this->model->Pengajuan_Alpa],
                                'lupa absen masuk' => ['LM', $this->model->Pengajuan_Lupa_Absen_Masuk],
                                'lupa absen pulang' => ['LP', $this->model->Pengajuan_Lupa_Absen_Pulang],
                                'datang terlambat' => ['DT', $this->model->Pengajuan_Datang_Terlambat],
                                'pulang cepat' => ['PC', $this->model->Pengajuan_Pulang_Cepat],
                            ];

                            if (isset($necessaryMap[$post['submissiontype']])) {
                                [$necessary, $submissionType] = $necessaryMap[$post['submissiontype']];
                                $this->entity->setNecessary($necessary);
                                $this->entity->setSubmissionType($submissionType);
                            }

                            // TODO : Checking Period
                            $period = $mYear->getPeriodStatus($submissionDate, $submissionType)->getRow();

                            if (empty($period)) {
                                return $this->response->setJSON(message('success', false, "Periode belum dibuat"));
                            } else if ($period->period_status == $this->PERIOD_CLOSED) {
                                return $this->response->setJSON(message('success', false, "Periode {$period->name} ditutup"));
                            }

                            if ($post['submissiontype'] == "datang terlambat") {
                                $subLine = (new M_AssignmentDate($this->request))->find($post['foreignkey']);

                                $submissionDate = $subLine->realization_in;
                            } else if ($post['submissiontype'] == "pulang cepat") {
                                $subLine = (new M_AssignmentDate($this->request))->find($post['foreignkey']);

                                $submissionDate = $subLine->realization_out;
                            }

                            // TODO : For get Document number need $post['md_employee_id']
                            $this->entity->setEmployeeId($employee->getEmployeeId());
                            $this->entity->setNik($employee->getNik());

                            $this->entity->setBranchId($trx->md_branch_id);
                            $this->entity->setDivisionId($trx->md_division_id);
                            $this->entity->setReceivedDate($todayTime);
                            $this->entity->setReason($post['reason']);
                            $this->entity->setSubmissionDate($today);
                            $this->entity->setStartDate($submissionDate);
                            $this->entity->setEndDate($submissionDate);

                            //* ini untuk Lupa Absen Masuk dan Lupa Absen Pulang
                            if ($post['submissiontype'] == "lupa absen masuk" || $post['submissiontype'] == "lupa absen pulang")
                                $this->entity->setEndDateRealization($submissionDate);

                            $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                            $post['submissiondate'] = $this->entity->getSubmissionDate();
                            $post['necessary'] = $necessary;

                            $docNo = $this->model->getInvNumber("submissiontype", $submissionType, $post, $this->session->get('sys_user_id'), false);
                            $this->entity->setDocumentNo($docNo);
                            $this->isNewRecord = true;

                            $response = $this->save();

                            // TODO : Insert Absent Detail New Submission 
                            $ID =  $this->insertID;

                            $this->model = new M_AbsentDetail($this->request);
                            $this->entity = new \App\Entities\AbsentDetail();

                            $this->entity->isagree = $this->LINESTATUS_Approval;
                            $this->entity->trx_absent_id = $ID;
                            $this->entity->lineno = 1;
                            $this->entity->date = $submissionDate;
                            $this->save();

                            // TODO : Set Line ID
                            $lineID = $this->insertID;

                            /**
                             * Update Old Submission
                             */
                            $this->isNewRecord = false;

                            $this->model = $isAssignment ? new M_AssignmentDate($this->request) : new M_AbsentDetail($this->request);
                            $this->entity = $isAssignment ? new \App\Entities\AssignmentDate() : new \App\Entities\AbsentDetail();
                            $this->entity->isagree = $this->LINESTATUS_Ditolak;
                            $this->entity->{$isAssignment ? 'trx_assignment_date_id' : 'trx_absent_detail_id'} = $post['foreignkey'];
                            $this->entity->table = 'trx_absent_detail';
                            $this->entity->{$isAssignment ? 'reference_id' : 'ref_absent_detail_id'} = $lineID;
                            $this->entity->realization_date_hrd = date('Y-m-d H:i:s');
                            $this->entity->realization_by_hrd = $this->access->getSessionUser();
                            $this->save();

                            /**
                             * Update Absent Detail New Submission 
                             */
                            $this->model = new M_AbsentDetail($this->request);
                            $this->entity = new \App\Entities\AbsentDetail();
                            $this->entity->ref_absent_detail_id = $post['foreignkey'];
                            $this->entity->isagree = $this->LINESTATUS_Disetujui;
                            $this->entity->table = $isAssignment ? 'trx_assignment_date' : 'trx_absent_detail';
                            $this->entity->trx_absent_detail_id = $lineID;
                            $this->save();

                            $this->model = new M_Absent($this->request);
                            $this->entity = new \App\Entities\Absent();
                            $this->entity->setDocStatus($this->DOCSTATUS_Completed);
                            $this->entity->setAbsentId($ID);
                            $this->save();
                        } else {
                            $this->entity->{$this->model->primaryKey} = $post['foreignkey'];
                            $this->entity->realization_date_hrd = date('Y-m-d H:i:s');
                            $this->entity->realization_by_hrd = $this->access->getSessionUser();
                            $this->entity->isagree = $this->LINESTATUS_Ditolak;
                            $response = $this->save();
                        }
                    }

                    // TODO Send Notification to Created User
                    $row = $model->find($trx->header_id);
                    $user = $mUser->where('sys_user_id', $row->created_by)->first();

                    $cMessage->sendInformation($user, $subject, $message, 'HARMONY SAS', null, null, true, true, true);

                    // TODO : Send Telegram Message to Employee
                    if (($user->md_employee_id != $employee->md_employee_id) && !empty($employee->telegram_id))
                        $cTelegram->sendMessage($employee->telegram_id, (new Html2Text($message))->getText());

                    // TODO : Update Header if There's no pending line
                    $where = "v_realization.header_id = {$trx->header_id}";
                    $where .= " AND v_realization.isagree IN ('{$this->LINESTATUS_Approval}','{$this->LINESTATUS_Realisasi_Atasan}','{$this->LINESTATUS_Realisasi_HRD}')";
                    $where .= " AND v_realization.table = '{$table}'";
                    $pendingLine = $mAbsentDetail->getAllSubmission($where)->getRow();

                    if (empty($pendingLine)) {
                        $dataUpdate = [
                            'updated_by'   => $this->access->getSessionUser(),
                            'updated_at'   => date('Y-m-d H:i:s'),
                            'docstatus'    => $this->DOCSTATUS_Completed,
                            'receiveddate' => $today,
                        ];

                        $model->builder->update($dataUpdate, [$model->primaryKey => $trx->header_id]);
                        $changeLog->insertLog($model->table, 'docstatus', $trx->header_id, $trx->docstatus, "CO", 'U');
                    }
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
                throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
            }

            return $this->response->setJSON($response);
        }
    }

    public function createOvertime()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();
            $mOvertimeDetail = new M_OvertimeDetail($this->request);
            $changeLog = new M_ChangeLog($this->request);
            $mEmployee = new M_Employee($this->request);
            $mNotifText = new M_NotificationText($this->request);
            $cTelegram = new Telegram();

            $today = date('Y-m-d');
            $todayTime = date('Y-m-d H:i:s');

            $isAgree = $post['isagree'];

            try {
                $trx = $mOvertimeDetail->getRealizationOvertime("v_realization_overtime.trx_overtime_detail_id = {$post['id']}")->getRow();
                $realizeDate = date('Y-m-d', strtotime($trx->realization_date));

                if (!$this->validation->run($post, 'realisasi_lembur_agree') && $isAgree == $this->LINESTATUS_Disetujui) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else if ($today < $realizeDate) {
                    $response = message('success', false, 'tanggal realisasi belum terpenuhi');
                } else {
                    $this->model = new M_OvertimeDetail($this->request);
                    $this->entity = new \App\Entities\OvertimeDetail();

                    $employee = $mEmployee->find($trx->md_employee_id);
                    $line = $this->model->find($post['id']);

                    $this->entity->trx_overtime_detail_id = $post['id'];
                    $this->entity->realization_date_superior = date('Y-m-d H:i:s');
                    $this->entity->realization_by_superior = $this->access->getSessionUser();

                    if ($isAgree == $this->LINESTATUS_Disetujui) {
                        // TODO : Set Notification
                        $dataNotif = $mNotifText->where('name', 'Realisasi Disetujui Atasan')->first();
                        $message = str_replace(['(Var1)', '(Var2)'], [$trx->documentno, date('Y-m-d', strtotime($line->startdate))], $dataNotif->getText());

                        $startdate = date('Y-m-d', strtotime($line->startdate)) . " " . $post['starttime'];
                        $enddate = date('Y-m-d', strtotime($post["enddate_realization"])) . " " . $post['endtime_realization'];

                        $ovt = $this->getHourOvertime($startdate, $enddate, $line->md_employee_id);

                        $this->entity->startdate = $startdate;
                        $this->entity->enddate_realization = $enddate;
                        $this->entity->isagree = $this->LINESTATUS_Disetujui;
                        $this->entity->overtime_expense = !empty($ovt) ? $ovt['expense'] : null;
                        $this->entity->overtime_balance = !empty($ovt) ? $ovt['balance'] : null;
                        $this->entity->total = !empty($ovt) ? $ovt['total'] : null;
                    } else {
                        // TODO : Set Notification
                        $dataNotif = $mNotifText->where('name', 'Realisasi Tidak Disetujui Atasan')->first();
                        $message = str_replace(['(Var1)', '(Var2)'], [$trx->documentno, date('Y-m-d', strtotime($line->startdate))], $dataNotif->getText());

                        $this->entity->description = $post['description'];
                        $this->entity->isagree = $this->LINESTATUS_Ditolak;
                    }

                    $response = $this->save();

                    // TODO : Send Telegram Message to Employee
                    if (!empty($employee->telegram_id))
                        $cTelegram->sendMessage($employee->telegram_id, (new Html2Text($message))->getText());

                    $pendingLine = $this->model->where(
                        'trx_overtime_id',
                        $line->trx_overtime_id
                    )->whereIn('isagree', [$this->LINESTATUS_Approval, $this->LINESTATUS_Realisasi_Atasan, $this->LINESTATUS_Realisasi_HRD])
                        ->first();

                    if (empty($pendingLine)) {
                        $mOvertime = new M_Overtime($this->request);

                        $dataUpdate = [
                            'updated_by'   => $this->access->getSessionUser(),
                            'updated_at'   => date('Y-m-d H:i:s'),
                            'docstatus'    => $this->DOCSTATUS_Completed,
                            'receiveddate' => $todayTime,
                        ];

                        $mOvertime->builder->update($dataUpdate, [$mOvertime->primaryKey => $line->trx_overtime_id]);
                        $changeLog->insertLog($mOvertime->table, 'docstatus', $line->trx_overtime_id, $trx->docstatus, "CO", 'U');
                    }
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function createAttendance()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();
            $file = $this->request->getFile('image');
            $mAbsent = new M_Absent($this->request);
            $mAbsentDetail = new M_AbsentDetail($this->request);
            $changeLog = new M_ChangeLog($this->request);
            $mEmployee = new M_Employee($this->request);
            $mUser = new M_User($this->request);
            $mNotifText = new M_NotificationText($this->request);
            $cMessage = new Message();
            $cTelegram = new Telegram();

            $today = date('Y-m-d');
            $post['submissiondate'] = trim(preg_replace('/\|.*/', '', $post['submissiondate']));
            $submissionDate = date('Y-m-d', strtotime($post['submissiondate']));

            $isAgree = $post['isagree'];
            $submissionForm = $post['submissionform'];
            $typeFormHalfDay = ['Lupa Absen Masuk', 'Lupa Absen Pulang', 'Datang Terlambat', 'Pulang Cepat'];
            $typeFormOfficeDuties = ['Tugas Kantor', 'Tugas Kantor Setengah Hari'];

            $table = $submissionForm == "Penugasan" ? "trx_assignment" : "trx_absent";

            $whereClause = "v_realization_new.id = {$post['id']}";
            $whereClause .= " AND v_realization_new.table = '{$table}'";
            $trx = $mAbsentDetail->getRealization($whereClause)->getRow();
            $realizeDate = date('Y-m-d', strtotime($trx->realization_mgr));

            try {
                if ($isAgree == $this->LINESTATUS_Disetujui && in_array($submissionForm, $typeFormHalfDay) ? !$this->validation->run($post, 'realisasi_kehadiran') : false) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else if ($isAgree == $this->LINESTATUS_Disetujui && ($submissionForm == 'Tugas Kantor' && !$this->validation->run($post, 'realisasi_tugaskantor'))) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else if ($isAgree == $this->LINESTATUS_Disetujui && ($submissionForm == 'Tugas Kantor Setengah Hari' && !$this->validation->run($post, 'realisasi_tugaskantor_setengah'))) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else if ($today < $realizeDate) {
                    $response = message('success', false, 'tanggal realisasi belum terpenuhi');
                } else {
                    $employee = $mEmployee->find($trx->md_employee_id);
                    $model = $submissionForm == "Penugasan" ? new M_Assignment($this->request) : new M_Absent($this->request);
                    $this->model = $submissionForm == "Penugasan" ? new M_AssignmentDate($this->request) : new M_AbsentDetail($this->request);
                    $this->entity = $submissionForm == "Penugasan" ? new \App\Entities\AssignmentDate() : new \App\Entities\AbsentDetail();

                    $this->entity->{$this->model->primaryKey} = $post['id'];
                    $this->entity->realization_date_superior = date('Y-m-d H:i:s');
                    $this->entity->realization_by_superior = $this->access->getSessionUser();

                    if ($isAgree == $this->LINESTATUS_Disetujui) {
                        // TODO : Set Notification
                        $dataNotif = $mNotifText->where('name', 'Realisasi Disetujui Atasan')->first();
                        $subject = $dataNotif->getSubject();
                        $message = str_replace(['(Var1)', '(Var2)'], [$trx->documentno, $submissionDate], $dataNotif->getText());

                        $this->entity->isagree = $this->LINESTATUS_Disetujui;

                        if ($submissionForm == "Penugasan") {
                            $this->entity->isagree = $this->LINESTATUS_Realisasi_HRD;
                            $this->entity->comment = $post['comment'];
                            $this->entity->branch_in = $post['branch_in'];
                            $this->entity->branch_out = $post['branch_out'];
                            $this->entity->realization_in = date('Y-m-d', strtotime($submissionDate)) . " " . $post['starttime_att'];
                            $this->entity->realization_out = date('Y-m-d', strtotime($submissionDate)) . " " . $post['endtime_att'];
                        } else if (in_array($submissionForm, $typeFormHalfDay)) {
                            $this->entity->date = $submissionDate . " " . $post['endtime_realization'];
                        } else if (in_array($submissionForm, $typeFormOfficeDuties)) {
                            $img_name = "";

                            $lenPos = strpos($employee->value, '-');
                            $value = substr_replace($employee->value, "", $lenPos);
                            $ymd = date('YmdHis');

                            if ($file && $file->isValid()) {
                                $path = $this->PATH_UPLOAD . $this->PATH_Pengajuan . '/';

                                $ext = $file->getClientExtension();

                                if ($submissionForm == "Tugas Kantor") {
                                    $img_name = $mAbsent->Pengajuan_Tugas_Kantor . '_' . $value . '_' . $ymd . '.' . $ext;
                                } else {
                                    $img_name = $mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari . '_' . $value . '_' . $ymd . '.' . $ext;
                                }

                                uploadFile($file, $path, $img_name);

                                $this->entity->image = $img_name;
                            }
                        }
                    } else {
                        // TODO : Set Notification
                        $dataNotif = $mNotifText->where('name', 'Realisasi Tidak Disetujui Atasan')->first();
                        $subject = $dataNotif->getSubject();
                        $message = str_replace(['(Var1)', '(Var2)'], [$trx->documentno, $submissionDate], $dataNotif->getText());

                        $this->entity->isagree = $this->LINESTATUS_Ditolak;
                        $this->entity->description = $post['description'];
                    }

                    $response = $this->save();

                    // TODO Send Notification to Created User
                    $row = $model->find($trx->header_id);
                    $user = $mUser->where('sys_user_id', $row->created_by)->first();

                    $cMessage->sendInformation($user, $subject, $message, 'HARMONY SAS', null, null, true, true, true);

                    // TODO : Send Telegram Message to Employee
                    if (($user->md_employee_id != $employee->md_employee_id) && !empty($employee->telegram_id))
                        $cTelegram->sendMessage($employee->telegram_id, (new Html2Text($message))->getText());

                    $where = "v_realization.header_id = {$trx->header_id}";
                    $where .= " AND v_realization.isagree IN ('{$this->LINESTATUS_Approval}','{$this->LINESTATUS_Realisasi_Atasan}','{$this->LINESTATUS_Realisasi_HRD}')";
                    $where .= " AND v_realization.table = '{$table}'";
                    $pendingLine = $mAbsentDetail->getAllSubmission($where)->getRow();

                    if (empty($pendingLine)) {
                        $dataUpdate = [
                            'updated_by'   => $this->access->getSessionUser(),
                            'updated_at'   => date('Y-m-d H:i:s'),
                            'docstatus'    => $this->DOCSTATUS_Completed,
                            'receiveddate' => $today,
                        ];

                        if ($isAgree == $this->LINESTATUS_Disetujui) {
                            if ($submissionForm === "Tugas Kantor Setengah Hari") {
                                $dataUpdate['startdate_realization'] = $submissionDate . " " . $post['starttime_att'];
                                $dataUpdate['enddate_realization']   = $submissionDate . " " . $post['endtime_att'];
                            } elseif (in_array($submissionForm, $typeFormHalfDay)) {
                                $dataUpdate['enddate_realization']   = $submissionDate . " " . $post['endtime_att'];
                            }
                        }

                        $model->builder->update($dataUpdate, [$model->primaryKey => $trx->header_id]);
                        $changeLog->insertLog($model->table, 'docstatus', $trx->header_id, $trx->docstatus, "CO", 'U');
                    }
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }
            return $this->response->setJSON($response);
        }
    }

    public function getList()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->getVar();

            $response = [];

            try {
                if (!empty($post['name']) && $post['name'] === "Penugasan") {
                    $list = [
                        [
                            'id'    => 'alpa',
                            'name'  => 'Alpa'
                        ],
                        [
                            'id'    => 'ijin',
                            'name'  => 'Ijin'
                        ],
                        [
                            'id'    => 'tidak setuju',
                            'name'  => 'Tidak Setuju'
                        ],
                    ];
                } else if (!empty($post['name']) && $post['name'] === "Ijin") {
                    $list = [
                        [
                            'id'    => 'alpa',
                            'name'  => 'Alpa'
                        ],
                        [
                            'id'    => 'tidak setuju',
                            'name'  => 'Tidak Setuju'
                        ],
                    ];
                } else if (!empty($post['name']) && $post['name'] === "Cuti") {
                    $list = [
                        [
                            'id'    => 'ijin',
                            'name'  => 'Ijin'
                        ],
                        [
                            'id'    => 'tidak setuju',
                            'name'  => 'Tidak Setuju'
                        ],
                    ];
                } else if (!empty($post['name']) && $post['name'] === "Pembatalan") {
                    $list = [
                        [
                            'id'    => 'tidak setuju',
                            'name'  => 'Tidak Setuju'
                        ]
                    ];
                } else {
                    $list = [
                        [
                            'id'    => 'alpa',
                            'name'  => 'Alpa'
                        ],
                        [
                            'id'    => 'ijin',
                            'name'  => 'Ijin'
                        ],
                        [
                            'id'    => 'tidak setuju',
                            'name'  => 'Tidak Setuju'
                        ],
                    ];
                }

                foreach ($list as $key => $row) :
                    $response[$key]['id'] = $row['id'];
                    $response[$key]['text'] = $row['name'];
                endforeach;
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function getHourOvertime($startdate, $endate, $md_employee_id)
    {
        $mRule = new M_Rule($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);

        $start = format_dmy($startdate, '-');
        $end = format_dmy($endate, '-');
        $today = date('Y-m-d');
        $day = date('w', strtotime($startdate));

        // Getting Rule
        $rule = $mRule->where([
            'name'      => 'Lembur',
            'isactive'  => 'Y'
        ])->first();

        $detail = $mRuleDetail->where('md_rule_id', $rule->md_rule_id)->find();

        // Getting Working Hour
        $workDay = $mEmpWork->where([
            'md_employee_id'    => $md_employee_id,
            'validfrom <='      => $today,
            'validto >='        => $today
        ])->orderBy('validfrom', 'ASC')->first();

        $dayName = strtoupper(formatDay_idn($day));

        if (!empty($workDay)) {
            $whereClause = "md_work_detail.isactive = 'Y'";
            $whereClause .= " AND md_employee_work.md_employee_id = $md_employee_id";
            $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
            $whereClause .= " AND md_day.name = '$dayName'";
            $work = $mWorkDetail->getWorkDetail($whereClause)->getRow();
        }

        $data = [];

        $starttime = format_time($startdate);
        $endtime = format_time($endate);
        // If a employee have a workday
        if (!empty($work)) {
            $startMinutes = convertToMinutes(format_time($starttime));
            $endMinutes = convertToMinutes(format_time($endtime));
            $endWork = convertToMinutes($work->endwork);

            // Var for startdate < enddate
            $endDayMinutes = convertToMinutes('23:59');

            if ($startMinutes >= $endWork) {

                if ($start === $end) {
                    $balance = ($endMinutes - $startMinutes) / 60;
                } else {
                    $balance = (($endDayMinutes - $startMinutes) + $endMinutes + 1) / 60;
                }

                $total = $detail[0]->value * (int) $balance;

                $data['balance'] = (int) $balance;
                $data['expense'] = $detail[0]->value;
                $data['total'] = $total;
            } else if ($startMinutes < $endWork) {
                $startMinutes = $endWork;

                if ($start === $end) {
                    $balance = ($endMinutes - $startMinutes) / 60;
                } else {
                    $balance = (($endDayMinutes - $startMinutes) + $endMinutes + 1) / 60;
                }

                $total = $detail[0]->value * (int) $balance;

                $data['balance'] = (int) $balance;
                $data['expense'] = $detail[0]->value;
                $data['total'] = $total;
            }
        } // If a Employee dont have a workday
        else {
            $startMinutes = convertToMinutes(format_time($starttime));
            $endMinutes = convertToMinutes(format_time($endtime));
            $endWork = '';

            // Var for startdate < enddate just one day
            $endDayMinutes = convertToMinutes('23:59');

            if ($day <= 5) {
                $endWork = convertToMinutes($detail[1]->value);
            } else {
                $endWork = convertToMinutes($detail[2]->value);
            }

            if ($startMinutes >= $endWork) {

                if ($start === $end) {
                    $balance = ($endMinutes - $startMinutes) / 60;
                } else {
                    $balance = (($endDayMinutes - $startMinutes) + $endMinutes + 1) / 60;
                }

                $total = $detail[0]->value * (int) $balance;

                $data['balance'] = (int) $balance;
                $data['expense'] = $detail[0]->value;
                $data['total'] = $total;
            } else if ($startMinutes < $endWork) {
                $startMinutes = $endWork;

                if ($start === $end) {
                    $balance = ($endMinutes - $startMinutes) / 60;
                } else {
                    $balance = (($endDayMinutes - $startMinutes) + $endMinutes + 1) / 60;
                }

                $total = $detail[0]->value * (int) $balance;

                $data['balance'] = (int) $balance;
                $data['expense'] = $detail[0]->value;
                $data['total'] = $total;
            }
        }

        return $data;
    }

    public function getImage($id)
    {
        $response = [];

        try {
            $row = $this->model->find($id);

            $response = [];

            if (!empty($row->getImage()))
                array_push($response, base_url('uploads/pengajuan/' . $row->getImage()));

            if (!empty($row->getImage2()))
                array_push($response, base_url('uploads/pengajuan/' . $row->getImage2()));

            if (!empty($row->getImage3()))
                array_push($response, base_url('uploads/pengajuan/' . $row->getImage3()));

            if (!empty($row->getImageMedical()))
                array_push($response, base_url('uploads/keterangan/' . $row->getImageMedical()));
        } catch (\Exception $e) {
            $response = message('error', false, $e->getMessage());
        }

        return $this->response->setJSON($response);
    }

    public function doApprovedRealization()
    {
        $mAbsentDetail = new M_AbsentDetail($this->request);
        $mConfig = new M_Configuration($this->request);
        $mHoliday = new M_Holiday($this->request);
        $mAttendance = new M_Attendance($this->request);
        $changeLog = new M_ChangeLog($this->request);
        $mNotifText = new M_NotificationText($this->request);
        $mUser = new M_User($this->request);
        $mEmployee = new M_Employee($this->request);
        $mOvertimeDetail = new M_OvertimeDetail($this->request);
        $mUserRole = new M_UserRole($this->request);
        $cTelegram = new Telegram();
        $cMessage = new Message();

        //TODO : Get Rule How Many Days to Auto Approve
        $rule = $mConfig->where(['name' => 'AUTO_APPROVE_REALIZATION', 'isactive' => 'Y'])->first();

        $typeFormHalfDay = [100010, 100011, 100012, 100013];
        $dataNotif = $mNotifText->where('name', 'Realisasi Disetujui Sistem')->first();
        $holiday = $mHoliday->getHolidayDate();

        $todayTime = date('Y-m-d H:i:s');
        $today = date('Y-m-d');

        $this->session->set([
            'sys_user_id'       => 100000,
        ]);

        // TODO : Get HR Role and Employee, then get Email Adrress each of HR Employee
        $hrRole = $mUserRole->whereIn('sys_role_id', [5, 6])->findAll();
        $listHrUser = array_column($hrRole, "sys_user_id");
        $hrUsers = $mUser->whereIn('sys_user_id', $listHrUser)->findAll();

        if ($rule && $rule->value > 0) {
            // TODO : Get Submission Excluded Overtime
            $where = "docstatus = '{$this->DOCSTATUS_Inprogress}'";
            $where .= " AND isapproved = 'Y'";
            $where .= " AND isagree IN ('{$this->LINESTATUS_Realisasi_Atasan}','{$this->LINESTATUS_Realisasi_HRD}')";

            $listApproved = $mAbsentDetail->getRealization($where)->getResult();

            if ($listApproved) {
                foreach ($listApproved as $row) {
                    // TODO : Skip Iteration if SubmissionType is Office Duties Or Half Office Duties
                    if ($row->submissiontype == 100007 || $row->submissiontype == 100009) {
                        continue;
                    }

                    //TODO : Update Detail Status to Approved
                    if ($row->table === "trx_submission_cancel") {
                        $this->model = new M_SubmissionCancel($this->request);
                        $this->modelDetail = new M_SubmissionCancelDetail($this->request);
                        $entity = new \App\Entities\SubmissionCancelDetail();
                    } else if ($row->table === "trx_assignment") {
                        $this->model = new M_Assignment($this->request);
                        $this->modelDetail = new M_AssignmentDate($this->request);
                        $entity = new \App\Entities\AssignmentDate();
                    } else {
                        $this->model = new M_Absent($this->request);
                        $this->modelDetail = new M_AbsentDetail($this->request);
                        $entity = new \App\Entities\AbsentDetail();
                    }

                    $isHRD = $row->isagree == $this->LINESTATUS_Realisasi_HRD ? true : false;

                    $dateApproved = addBusinessDays(
                        $isHRD ? $row->realization_hrd : $row->realization_mgr,
                        $rule->value,
                        $holiday
                    );

                    // TODO : Continue Iteration when DateApproved not meet condition
                    if (!($dateApproved <= $today)) {
                        continue;
                    }

                    $trx = $this->model->find($row->header_id);
                    $trxLine = $this->modelDetail->find($row->id);
                    $date = date('Y-m-d', strtotime($row->date));

                    // TODO : Set Notification
                    $subject = $dataNotif->getSubject();
                    $message = str_replace(['(Var1)', '(Var2)'], [$trx->documentno, $date], $dataNotif->getText());

                    if (!$isHRD) {
                        $entity->realization_date_superior = date('Y-m-d H:i:s');
                        $entity->realization_by_superior = $this->access->getSessionUser();

                        if ($row->submissiontype == 100008) {
                            $whereIn = " v_attendance_branch.md_employee_id = {$row->md_employee_id}";
                            $whereIn .= " AND v_attendance_branch.date = '{$date}'";
                            $whereIn .= " AND v_attendance_branch.md_branch_id = {$trxLine->branch_in}";
                            $clock_in = $mAttendance->getAttBranch($whereIn)->getRow();

                            $whereOut = " v_attendance_branch.md_employee_id = {$row->md_employee_id}";
                            $whereOut .= " AND v_attendance_branch.date = '{$date}'";
                            $whereOut .= " AND v_attendance_branch.md_branch_id = {$trxLine->branch_out}";
                            $clock_out = $mAttendance->getAttBranch($whereOut)->getRow();

                            $isAgreeUpdate = $this->LINESTATUS_Realisasi_HRD;
                            $startTime = $clock_in ? $clock_in->clock_in : '';
                            $endTime = $clock_out ? $clock_out->clock_out : '';

                            $entity->realization_in = $date . " " . $startTime;
                            $entity->realization_out = $date . " " . $endTime;
                        } else {
                            $isAgreeUpdate = $this->LINESTATUS_Disetujui;

                            if (in_array($row->submissiontype, $typeFormHalfDay))
                                $entity->date = $date . " " . format_time($trx->startdate);
                        }
                    } else {
                        $isAgreeUpdate = $this->LINESTATUS_Disetujui;
                        $entity->realization_date_hrd = date('Y-m-d H:i:s');
                        $entity->realization_by_hrd = $this->access->getSessionUser();

                        if ($row->submissiontype == 100008) {
                            $whereIn = " v_attendance_branch.md_employee_id = {$row->md_employee_id}";
                            $whereIn .= " AND v_attendance_branch.date = '{$date}'";
                            $whereIn .= " AND v_attendance_branch.md_branch_id = {$trxLine->branch_in}";
                            $clock_in = $mAttendance->getAttBranch($whereIn)->getRow();

                            $whereOut = " v_attendance_branch.md_employee_id = {$row->md_employee_id}";
                            $whereOut .= " AND v_attendance_branch.date = '{$date}'";
                            $whereOut .= " AND v_attendance_branch.md_branch_id = {$trxLine->branch_out}";
                            $clock_out = $mAttendance->getAttBranch($whereOut)->getRow();

                            $startTime = $clock_in ? $clock_in->clock_in : '';
                            $endTime = $clock_out ? $clock_out->clock_out : '';

                            $entity->realization_in = $date . " " . $startTime;
                            $entity->realization_out = $date . " " . $endTime;

                            if (empty($startTime) || empty($endTime)) {
                                $clock_in = null;
                                $clock_out = null;

                                if (empty($startTime)) {
                                    $whereIn = " v_attendance_branch.md_employee_id = {$row->md_employee_id}";
                                    $whereIn .= " AND v_attendance_branch.date = '{$date}'";
                                    $whereIn .= " AND v_attendance_branch.clock_in != ''";
                                    $whereIn .= " AND v_attendance_branch.md_branch_id != {$trxLine->branch_in}";
                                    $clock_in = $mAttendance->getAttBranch($whereIn)->getRow();
                                }

                                if (empty($endTime)) {
                                    $whereOut = " v_attendance_branch.md_employee_id = {$row->md_employee_id}";
                                    $whereOut .= " AND v_attendance_branch.date = '{$date}'";
                                    $whereOut .= " AND v_attendance_branch.clock_out != ''";
                                    $whereOut .= " AND v_attendance_branch.md_branch_id != {$trxLine->branch_out}";
                                    $clock_out = $mAttendance->getAttBranch($whereOut)->getRow();
                                }

                                if (empty($clock_in) && empty($clock_out)) {
                                    $entity->instruction_in = 'N';
                                    $entity->instruction_out = 'N';
                                } else if (empty($clock_in)) {
                                    $entity->instruction_in = 'N';
                                    $entity->instruction_out = 'Y';
                                } else if (empty($clock_out)) {
                                    $entity->instruction_in = 'Y';
                                    $entity->instruction_out = 'N';
                                }

                                // if (empty($startTime) && empty($clock_in)) {
                                //     continue;
                                // } else if (empty($endTime) && empty($clock_out)) {
                                //     continue;
                                // }
                            } else {
                                $entity->instruction_in = 'Y';
                                $entity->instruction_out = 'Y';
                            }
                        }
                    }

                    $entity->isagree = $isAgreeUpdate;
                    $entity->updated_at = $todayTime;
                    $entity->updated_by = $this->session->get('sys_user_id');
                    $entity->{$this->modelDetail->primaryKey} = $row->id;

                    $result = $this->modelDetail->save($entity);

                    if ($result) {
                        $changeLog->insertLog($this->modelDetail->table, 'isagree', $row->id, $row->isagree, $isAgreeUpdate, $this->EVENTCHANGELOG_Update);

                        // TODO : Send Information
                        $user = $mUser->where('sys_user_id', $trx->created_by)->first();
                        $employee = $mEmployee->find($row->md_employee_id);
                        $cMessage->sendInformation($user, $subject, $message, 'HARMONY SAS', null, null, true, true, true);

                        // TODO : Send Telegram Message to Employee
                        if (($user->md_employee_id != $employee->md_employee_id) && !empty($employee->telegram_id))
                            $cTelegram->sendMessage($employee->telegram_id, (new Html2Text($message))->getText());

                        // TODO : Send Information to HRD
                        if ($isHRD) {
                            foreach ($hrUsers as $hrUser)
                                $cMessage->sendInformation($hrUser, $subject, $message, 'HARMONY SAS', null, null, true, true, true);
                        }

                        //TODO : Update Header Status to Complete if There's No Another Line to Realization
                        $where = "v_realization.docstatus = '{$this->DOCSTATUS_Inprogress}'";
                        $where .= " AND v_realization.isagree IN ('{$this->LINESTATUS_Realisasi_Atasan}','{$this->LINESTATUS_Realisasi_HRD}','{$this->LINESTATUS_Approval}')";
                        $where .= " AND v_realization.header_id = {$row->header_id}";
                        $where .= " AND v_realization.table = '{$row->table}'";
                        $pendingLine = $mAbsentDetail->getAllSubmission($where)->getRow();

                        if (!$pendingLine) {
                            $dataUpdate = [
                                'updated_by'   =>  $this->session->get('sys_user_id'),
                                'updated_at'   => $todayTime,
                                'docstatus'    => $this->DOCSTATUS_Completed,
                                'receiveddate' => $today,
                            ];

                            if ($row->submissiontype == 100009) {
                                $dataUpdate['startdate_realization'] = $date . " " . format_time($trx->startdate);
                                $dataUpdate['enddate_realization']   = $date . " " . format_time($trx->enddate);
                            } elseif (in_array($row->submissiontype, $typeFormHalfDay)) {
                                $dataUpdate['enddate_realization']   = $date . " " . format_time($trx->enddate);
                            }

                            $this->model->builder->update($dataUpdate, [$this->model->primaryKey => $row->header_id]);
                            $changeLog->insertLog($this->model->table, 'docstatus', $row->header_id, $row->docstatus, $this->DOCSTATUS_Completed, $this->EVENTCHANGELOG_Update);
                        }
                    } else {
                        log_message('error', json_encode($result));
                    }
                }
            } else {
                log_message('info', 'Tidak ada transaksi realisasi');
            }

            // TODO : This segment is for auto approve Overtime
            $where = "isagree = '{$this->LINESTATUS_Realisasi_Atasan}'";
            $listApprovedOvertime = $mOvertimeDetail->getRealizationOvertime($where)->getResult();

            if ($listApprovedOvertime) {
                foreach ($listApprovedOvertime as $row) {
                    //TODO : Update Detail Status to Approved
                    $this->model = new M_Overtime($this->request);
                    $this->modelDetail = new M_OvertimeDetail($this->request);
                    $entity = new \App\Entities\OvertimeDetail();

                    $dateApproved = addBusinessDays(
                        $row->realization_date,
                        $rule->value,
                        $holiday
                    );

                    // TODO : Continue Iteration when DateApproved not meet condition
                    if (!($dateApproved <= $today)) {
                        continue;
                    }

                    // TODO : Get Transaction Data
                    $trxLine = $this->modelDetail->find($row->trx_overtime_detail_id);
                    $trx = $this->model->find($trxLine->trx_overtime_id);
                    $date = date('Y-m-d', strtotime($row->startdate_line));

                    // TODO : Set Notification
                    $subject = $dataNotif->getSubject();
                    $message = str_replace(['(Var1)', '(Var2)'], [$row->documentno, $date], $dataNotif->getText());

                    // TODO : Get Employee Clock Out
                    $where = " v_attendance.md_employee_id = {$row->md_employee_id}";
                    $where .= " AND v_attendance.date = '{$date}'";
                    $where .= " AND v_attendance.clock_out != ''";
                    $clock_out = $mAttendance->getAttendance($where)->getRow();

                    $enddate = $date . " " . (!empty($clock_out) ? $clock_out->clock_out : '');

                    $ovt = null;
                    $isagree = null;
                    if (!empty($clock_out)) {
                        $ovt = $this->getHourOvertime($row->startdate_line, $enddate, $row->md_employee_id);

                        $isagree = $this->LINESTATUS_Disetujui;
                        $entity->enddate_realization = $enddate;
                    } else {
                        $isagree = $this->LINESTATUS_Ditolak;
                    }

                    $entity->overtime_expense = !empty($ovt) ? $ovt['expense'] : null;
                    $entity->overtime_balance = !empty($ovt) ? $ovt['balance'] : null;
                    $entity->total = !empty($ovt) ? $ovt['total'] : null;
                    $entity->isagree = $isagree;
                    $entity->updated_at = $todayTime;
                    $entity->updated_by = $this->session->get('sys_user_id');
                    $entity->realization_date_superior = date('Y-m-d H:i:s');
                    $entity->realization_by_superior = $this->access->getSessionUser();
                    $entity->{$this->modelDetail->primaryKey} = $row->trx_overtime_detail_id;

                    $result = $this->modelDetail->save($entity);

                    if ($result) {
                        $changeLog->insertLog($this->modelDetail->table, 'isagree', $row->trx_overtime_detail_id, $row->isagree, $isagree, $this->EVENTCHANGELOG_Update);

                        // TODO : Send Information
                        $user = $mUser->where('sys_user_id', $trx->created_by)->first();
                        $employee = $mEmployee->find($row->md_employee_id);
                        $cMessage->sendInformation($user, $subject, $message, 'HARMONY SAS', null, null, true, true, true);

                        // TODO : Send Telegram Message to Employee
                        if (($user->md_employee_id != $employee->md_employee_id) && !empty($employee->telegram_id))
                            $cTelegram->sendMessage($employee->telegram_id, (new Html2Text($message))->getText());

                        //TODO : Update Header Status to Complete if There's No Another Line to Realization
                        $pendingLine = $this->modelDetail->where(
                            'trx_overtime_id',
                            $trxLine->trx_overtime_id
                        )->whereIn('isagree', [$this->LINESTATUS_Approval, $this->LINESTATUS_Realisasi_Atasan, $this->LINESTATUS_Realisasi_HRD])
                            ->first();

                        if (!$pendingLine) {
                            $dataUpdate = [
                                'updated_by'   =>  $this->session->get('sys_user_id'),
                                'updated_at'   => $todayTime,
                                'docstatus'    => $this->DOCSTATUS_Completed,
                                'receiveddate' => $today,
                            ];

                            $this->model->builder->update($dataUpdate, [$this->model->primaryKey => $trx->trx_overtime_id]);
                            $changeLog->insertLog($this->model->table, 'docstatus', $trx->trx_overtime_id, $trx->docstatus, $this->DOCSTATUS_Completed, $this->EVENTCHANGELOG_Update);
                        }
                    } else {
                        log_message('error', json_encode($result));
                    }
                }
            } else {
                log_message('info', 'Tidak ada transaksi realisasi lembur');
            }
        }
    }
}

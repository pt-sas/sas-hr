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
use App\Models\M_NotificationText;
use App\Models\M_SubmissionCancel;
use App\Models\M_SubmissionCancelDetail;
use App\Models\M_User;
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
            $table = 'v_realization';
            $select = '*';
            $join = [];
            $order = [
                '', // Number
                'v_realization.date',
                'v_realization.doctype',
                'v_realization.branch',
                'v_realization.division',
                'v_realization.employee_fullname',
                '',
                'v_realization.reason'
            ];
            $search = $this->request->getPost('search');
            $sort = ['v_realization.date' => 'ASC', 'v_realization.employee_fullname' => 'ASC'];

            $formType = [
                $this->model->Pengajuan_Lupa_Absen_Masuk,
                $this->model->Pengajuan_Lupa_Absen_Pulang,
                $this->model->Pengajuan_Datang_Terlambat,
                $this->model->Pengajuan_Pulang_Cepat,
            ];

            $where = [
                "docstatus = '{$this->DOCSTATUS_Inprogress}'
                AND isagree = 'S' 
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

                $number++;

                $reason = $value->reason;

                if ($value->comment)
                    $reason .= " | <small class='text-danger'>{$value->comment}</small>";

                $row[] = $number;
                $row[] = format_dmy($value->date, '-');
                $row[] = $value->doctype;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = $value->employee_fullname;
                $row[] = viewImage($value->header_id, $value->image);
                $row[] = $reason;
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

    public function showAllOvertime()
    {
        $mAccess = new M_AccessMenu($this->request);
        $mOvertime = new M_Overtime($this->request);
        $mAttendance = new M_Attendance($this->request);
        $mEmployee = new M_Employee($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $table = 'v_realization_overtime';
            $select = '*';
            $join = [];
            $order = [
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

            /**
             * Hak akses
             */
            $roleEmp = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_All_Data');
            $arrAccess = $mAccess->getAccess($this->session->get("sys_user_id"));
            $arrEmployee = $mEmployee->getChartEmployee($this->session->get('md_employee_id'));

            if ($arrAccess && isset($arrAccess["branch"]) && isset($arrAccess["division"])) {
                $arrBranch = $arrAccess["branch"];
                $arrDiv = $arrAccess["division"];

                $arrEmpBased = $mEmployee->getEmployeeBased($arrBranch, $arrDiv);

                if ($roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $arrMerge = array_unique(array_merge($arrEmpBased, $arrEmployee));

                    $where['md_employee_id'] = [
                        'value'     => $arrMerge
                    ];
                } else if (!$roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $where['md_employee_id'] = [
                        'value'     => $arrEmployee
                    ];
                } else if ($roleEmp && empty($this->session->get('md_employee_id'))) {
                    $where['md_employee_id'] = [
                        'value'     => $arrEmpBased
                    ];
                } else {
                    $where['md_employee_id'] = $this->session->get('md_employee_id');
                }
            } else if (!empty($this->session->get('md_employee_id'))) {
                $where['md_employee_id'] = [
                    'value'     => $arrEmployee
                ];
            } else {
                $where['md_employee_id'] = $this->session->get('md_employee_id');
            }

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];

                $startDateLine = date('Y-m-d', strtotime($value->startdate_line));

                $whereClause = "v_attendance.md_employee_id = {$value->md_employee_id}";
                $whereClause .= " AND v_attendance.date = '{$startDateLine}'";
                $whereClause .= " AND v_attendance.clock_out IS NOT NULL";
                $attendance = $mAttendance->getAttendance($whereClause)->getRow();

                $ID = $value->trx_overtime_detail_id;

                $number++;

                $row[] = $number;
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
        $mAccess = new M_AccessMenu($this->request);
        $mEmployee = new M_Employee($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $table = 'v_realization';
            $select = '*';
            $join = [];
            $order = [
                '', // Number
                'v_realization.date',
                '',
                'v_realization.documentno',
                'v_realization.doctype',
                'v_realization.branch',
                'v_realization.division',
                'v_realization.employee_fullname',
                '',
                '',
                'v_realization.reason'
            ];
            $search = $this->request->getPost('search');
            $sort = ['v_realization.date' => 'ASC', 'v_realization.employee_fullname' => 'ASC'];

            $where['docstatus'] = $this->DOCSTATUS_Inprogress;
            $where['isagree'] = 'M';

            /**
             * Hak akses
             */
            $roleEmp = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_All_Data');
            $arrAccess = $mAccess->getAccess($this->session->get("sys_user_id"));
            $arrEmployee = $mEmployee->getChartEmployee($this->session->get('md_employee_id'));

            if ($arrAccess && isset($arrAccess["branch"]) && isset($arrAccess["division"])) {
                $arrBranch = $arrAccess["branch"];
                $arrDiv = $arrAccess["division"];

                $arrEmpBased = $mEmployee->getEmployeeBased($arrBranch, $arrDiv);

                if ($roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $arrMerge = array_unique(array_merge($arrEmpBased, $arrEmployee));

                    $where['md_employee_id'] = [
                        'value'     => $arrMerge
                    ];
                } else if (!$roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $where['md_employee_id'] = [
                        'value'     => $arrEmployee
                    ];
                } else if ($roleEmp && empty($this->session->get('md_employee_id'))) {
                    $where['md_employee_id'] = [
                        'value'     => $arrEmpBased
                    ];
                } else {
                    $where['md_employee_id'] = $this->session->get('md_employee_id');
                }
            } else if (!empty($this->session->get('md_employee_id'))) {
                $where['md_employee_id'] = [
                    'value'     => $arrEmployee
                ];
            } else {
                $where['md_employee_id'] = $this->session->get('md_employee_id');
            }

            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            $number = $this->request->getPost('start');
            $data = [];
            foreach ($list as $value) :
                $row = [];
                $ID = $value->id;

                $tanggal = '';
                $clock = '';

                $startDate = date('Y-m-d', strtotime($value->date));

                $whereClause = "v_attendance.md_employee_id = {$value->md_employee_id}";
                $whereClause .= " AND v_attendance.date = '{$startDate}'";

                if ($value->submissiontype == $mAbsent->Pengajuan_Pulang_Cepat)
                    $whereClause .= " AND v_attendance.clock_out IS NOT NULL";
                else if ($value->submissiontype == $mAbsent->Pengajuan_Datang_Terlambat)
                    $whereClause .= " AND v_attendance.clock_in IS NOT null";

                $attendance = $mAttendance->getAttendance($whereClause)->getRow();

                if ($attendance && $value->submissiontype == $mAbsent->Pengajuan_Pulang_Cepat) {
                    $tanggal = format_dmy($attendance->date, '-');
                    $absent = format_time($attendance->clock_out);
                    $clock = format_time($value->date);
                } else if ($attendance && $value->submissiontype == $mAbsent->Pengajuan_Datang_Terlambat) {
                    $tanggal = format_dmy($attendance->date, '-');
                    $absent = format_time($attendance->clock_in);
                    $clock = format_time($value->date);
                }

                $number++;

                $row[] = $number;
                $row[] = format_dmy($value->date, '-');
                $row[] = $clock ?? '';
                $row[] = $value->documentno;
                $row[] = $value->doctype;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = $value->employee_fullname;
                $row[] = $tanggal ?? '';
                $row[] = $absent ?? '';
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
            $cMessage = new Message();
            $cTelegram = new Telegram();

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
                $whereClause = "v_realization.id = {$post['id']}";
            } else {
                $whereClause = "v_realization.id = {$post['foreignkey']}";
            }

            $whereClause .= " AND v_realization.table = '{$table}'";
            $trx = $mAbsentDetail->getAllSubmission($whereClause)->getRow();
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
                        $this->entity->realization_by = $this->access->getSessionUser();

                        if ($submissionForm == "Penugasan") {
                            $this->entity->realization_in = date('Y-m-d', strtotime($post['submissiondate'])) . " " . $post['starttime_att'];
                            $this->entity->realization_out = date('Y-m-d', strtotime($post['submissiondate'])) . " " . $post['endtime_att'];

                            if (empty($post['starttime_att']) || empty($post['endtime_att'])) {

                                $clock_in = null;
                                $clock_out = null;

                                if (empty($post['starttime_att'])) {
                                    $whereIn = " v_attendance_serialnumber.md_employee_id = {$trx->md_employee_id}";
                                    $whereIn .= " AND v_attendance_serialnumber.date = '{$submissionDate}'";
                                    $whereIn .= " AND v_attendance_serialnumber.clock_in IS NOT null";
                                    $whereIn .= " AND md_attendance_machines.md_branch_id != {$post['branch_in']}";
                                    $clock_in = $mAttendance->getAttendanceBranch($whereIn)->getRow();
                                }

                                if (empty($post['endtime_att'])) {
                                    $whereOut = " v_attendance_serialnumber.md_employee_id = {$trx->md_employee_id}";
                                    $whereOut .= " AND v_attendance_serialnumber.date = '{$submissionDate}'";
                                    $whereOut .= " AND v_attendance_serialnumber.clock_out IS NOT null";
                                    $whereOut .= " AND md_attendance_machines.md_branch_id != {$post['branch_out']}";
                                    $clock_out = $mAttendance->getAttendanceBranch($whereOut)->getRow();
                                }

                                if ($clock_in && $clock_out) {
                                    $this->entity->instruction_in = 'N';
                                    $this->entity->instruction_out = 'N';
                                } else if ($clock_in) {
                                    $this->entity->instruction_in = 'N';
                                    $this->entity->instruction_out = 'Y';
                                } else if ($clock_out) {
                                    $this->entity->instruction_in = 'Y';
                                    $this->entity->instruction_out = 'N';
                                }

                                if (empty($post['starttime_att']) && !isset($clock_in)) {
                                    $response = message('success', false, "Tidak ada absensi masuk di cabang lain");
                                } else if (empty($post['endtime_att']) && !isset($clock_out)) {
                                    $response = message('success', false, "Tidak ada absensi pulang di cabang lain");
                                } else {
                                    $response = $this->save();
                                }
                            } else {
                                $this->entity->instruction_in = 'Y';
                                $this->entity->instruction_out = 'Y';
                                $response = $this->save();
                            }
                        } else {
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
                            $this->entity->realization_by = $this->access->getSessionUser();
                            $this->entity->{$isAssignment ? 'trx_assignment_date_id' : 'trx_absent_detail_id'} = $post['foreignkey'];
                            $this->entity->table = 'trx_absent_detail';
                            $this->entity->{$isAssignment ? 'reference_id' : 'ref_absent_detail_id'} = $lineID;
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
                            $this->entity->{$this->model->primaryKey} = $post['id'];
                            $this->entity->realization_by = $this->access->getSessionUser();
                            $this->entity->isagree = $this->LINESTATUS_Ditolak;
                            $this->entity->{$this->model->primaryKey} = $post['foreignkey'];
                            $response = $this->save();
                        }
                    }

                    // TODO Send Notification to Created User
                    $row = $model->find($trx->header_id);
                    $user = $mUser->where('sys_user_id', $row->created_by)->first();

                    $cMessage->sendInformation($user, $subject, $message, 'SAS HRD', null, null, true, true, true);

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
                    $this->entity->realization_by = $this->access->getSessionUser();

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
            $mAbsentDetail = new M_AbsentDetail($this->request);
            $changeLog = new M_ChangeLog($this->request);
            $mEmployee = new M_Employee($this->request);
            $mUser = new M_User($this->request);
            $mNotifText = new M_NotificationText($this->request);
            $cMessage = new Message();
            $cTelegram = new Telegram();

            $today = date('Y-m-d');
            $submissionDate = date('Y-m-d', strtotime($post['submissiondate']));

            $isAgree = $post['isagree'];
            $submissionForm = $post['submissionform'];
            $typeFormHalfDay = ['Lupa Absen Masuk', 'Lupa Absen Pulang', 'Datang Terlambat', 'Pulang Cepat'];

            $table = $submissionForm == "Penugasan" ? "trx_assignment" : "trx_absent";

            $whereClause = "v_realization.id = {$post['id']}";
            $whereClause .= " AND v_realization.table = '{$table}'";
            $trx = $mAbsentDetail->getAllSubmission($whereClause)->getRow();
            $realizeDate = date('Y-m-d', strtotime($trx->realization_mgr));

            try {
                if ($isAgree == $this->LINESTATUS_Disetujui && in_array($submissionForm, $typeFormHalfDay) ? !$this->validation->run($post, 'realisasi_kehadiran') : false) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else if ($today < $realizeDate) {
                    $response = message('success', false, 'tanggal realisasi belum terpenuhi');
                } else {
                    $employee = $mEmployee->find($trx->md_employee_id);
                    $model = $submissionForm == "Penugasan" ? new M_Assignment($this->request) : new M_Absent($this->request);
                    $this->model = $submissionForm == "Penugasan" ? new M_AssignmentDate($this->request) : new M_AbsentDetail($this->request);
                    $this->entity = $submissionForm == "Penugasan" ? new \App\Entities\AssignmentDate() : new \App\Entities\AbsentDetail();

                    $this->entity->{$this->model->primaryKey} = $post['id'];
                    $this->entity->realization_by = $this->access->getSessionUser();

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
                            $this->entity->date = date('Y-m-d', strtotime($post["enddate_realization"])) . " " . $post['endtime_realization'];
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

                    $cMessage->sendInformation($user, $subject, $message, 'SAS HRD', null, null, true, true, true);


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
                        ]
                    ];
                } else if (!empty($post['name']) && $post['name'] === "Cuti") {
                    $list = [
                        [
                            'id'    => 'ijin',
                            'name'  => 'Ijin'
                        ]
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
        $changeLog = new M_ChangeLog($this->request);

        //TODO : Get Rule How Many Days to Auto Approve
        $rule = $mConfig->where(['name' => 'AUTO_APPROVE_REALIZATION', 'isactive' => 'Y'])->first();

        if ($rule && $rule->value > 0) {

            $where = "docstatus = '{$this->DOCSTATUS_Inprogress}'";
            $where .= " AND isapproved = 'Y'";
            $where .= " AND isagree IN ('M','S')";
            $where .= " AND ADDDATE(date, INTERVAL {$rule->value} DAY) <= NOW()";

            $listApproved = $mAbsentDetail->getAllSubmission($where)->getResult();

            if ($listApproved) {
                $this->session->set([
                    'sys_user_id'       => 100000,
                ]);

                $todayTime = date('Y-m-d H:i:s');

                foreach ($listApproved as $row) {
                    //TODO : Update Detail Status to Approved
                    $this->modelDetail = $row->table === 'trx_absent' ? new M_AbsentDetail($this->request) : new M_AssignmentDate($this->request);
                    $entity = $row->table === "trx_absent" ? new \App\Entities\AbsentDetail() : new \App\Entities\AssignmentDate();

                    $entity->isagree = 'Y';
                    $entity->updated_at = $todayTime;
                    $entity->updated_by = $this->session->get('sys_user_id');
                    $entity->{$this->modelDetail->primaryKey} = $row->id;

                    if ($this->modelDetail->save($entity)) {
                        $changeLog->insertLog($this->modelDetail->table, 'isagree', $row->id, $row->isagree, 'Y', $this->EVENTCHANGELOG_Update);
                    };

                    //TODO : Update Header Status to Complete if There's No Another Line to Realization
                    $where = "docstatus = '{$this->DOCSTATUS_Inprogress}'";
                    $where .= " AND isapproved = 'Y'";
                    $where .= " AND isagree IN ('M','S','H')";
                    $where .= " AND header_id = {$row->header_id}";
                    $where .= " AND table = '{$row->table}'";
                    $remaining = $mAbsentDetail->getAllSubmission($where)->getRow();

                    if (!$remaining) {
                        $this->model = $row->table === 'trx_absent' ? new M_Absent($this->request) : new M_Assignment($this->request);
                        $this->entity = $row->table === 'trx_absent' ? new \App\Entities\Absent() : new \App\Entities\Assignment();

                        $this->entity->setDocStatus($this->DOCSTATUS_Completed);
                        $this->entity->setReceivedDate($todayTime);

                        if ($row->table === 'trx_absent') {
                            $this->entity->setAbsentId($row->header_id);
                        } else {
                            $this->entity->setAssignmentId($row->header_id);
                        }

                        $this->save();
                    }
                }
            }
        }
    }
}
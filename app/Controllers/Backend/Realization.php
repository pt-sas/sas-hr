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
use App\Models\M_AllowanceAtt;
use App\Models\M_Assignment;
use App\Models\M_AssignmentDate;
use App\Models\M_AssignmentDetail;
use App\Models\M_EmpBenefit;
use App\Models\M_Employee;
use App\Models\M_RuleValue;
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
                'realization.date',
                'realization.doctype',
                'realization.branch',
                'realization.division',
                'realization.employee_fullname',
                '',
                'realization.reason'
            ];
            $search = $this->request->getPost('search');
            $sort = ['realization.date' => 'ASC', 'realization.employee_fullname' => 'ASC'];

            $formType = [
                $this->model->Pengajuan_Lupa_Absen_Masuk,
                $this->model->Pengajuan_Lupa_Absen_Pulang,
                $this->model->Pengajuan_Datang_Terlambat,
                $this->model->Pengajuan_Pulang_Cepat,
            ];

            $where = [
                "docstatus = '{$this->DOCSTATUS_Inprogress}' 
                AND isapproved = 'Y' 
                AND isagree = 'H' 
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
            $table = $mOvertime->table;
            $select = $mOvertime->getSelectDetail();
            $join = $mOvertime->getJoinDetail();
            $order = $this->request->getPost('columns');
            $search = $this->request->getPost('search');
            $sort = ['trx_overtime.documentno' => 'ASC'];

            $where['trx_overtime.docstatus'] = $this->DOCSTATUS_Inprogress;
            $where['trx_overtime_detail.status'] = 'H';
            $where['trx_overtime.isapproved'] = 'Y';

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

                    $where['trx_overtime.md_employee_id'] = [
                        'value'     => $arrMerge
                    ];
                } else if (!$roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $where['trx_overtime.md_employee_id'] = [
                        'value'     => $arrEmployee
                    ];
                } else if ($roleEmp && empty($this->session->get('md_employee_id'))) {
                    $where['trx_overtime.md_employee_id'] = [
                        'value'     => $arrEmpBased
                    ];
                } else {
                    $where['trx_overtime.md_employee_id'] = $this->session->get('md_employee_id');
                }
            } else if (!empty($this->session->get('md_employee_id'))) {
                $where['trx_overtime.md_employee_id'] = [
                    'value'     => $arrEmployee
                ];
            } else {
                $where['trx_overtime.md_employee_id'] = $this->session->get('md_employee_id');
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
                'realization.date',
                '',
                'realization.documentno',
                'realization.doctype',
                'realization.branch',
                'realization.division',
                'realization.employee_fullname',
                '',
                '',
                'realization.reason'
            ];
            $search = $this->request->getPost('search');
            $sort = ['realization.date' => 'ASC', 'realization.employee_fullname' => 'ASC'];

            $where['docstatus'] = $this->DOCSTATUS_Inprogress;
            $where['isagree'] = 'M';
            $where['isapproved'] = 'Y';

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

            $agree = 'Y';
            $notAgree = 'N';
            $holdAgree = 'H';

            $isAgree = $post['isagree'];
            $submissionDate = date('Y-m-d', strtotime($post['submissiondate']));
            $today = date('Y-m-d');
            $todayTime = date('Y-m-d H:i:s');

            if (isset($post['md_leavetype_id']))
                $leaveTypeId = $post['md_leavetype_id'];

            $submissionForm = $post['submissionform'];
            $typeFormAssignment = ['Tugas Kantor', 'Penugasan'];

            try {
                if (
                    // $isAgree === 'Y' && ($submissionForm == "Penugasan" ? !$this->validation->run($post, 'realisasi_agree_penugasan') : 
                    !$this->validation->run($post, 'realisasi_agree')
                ) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else if (!$this->validation->run($post, 'realisasi_not_agree') && $isAgree === 'N') {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else if ($submissionDate > $today) {
                    $response = message('success', false, 'tanggal realisasi belum terpenuhi');
                } else {
                    $isAssignment = in_array($submissionForm, $typeFormAssignment);

                    if ($isAgree === $agree) {
                        // Set model dan entity sesuai kondisi tipe Form
                        $this->model = $isAssignment ? new M_AssignmentDate($this->request) : new M_AbsentDetail($this->request);
                        $this->entity = $isAssignment ? new \App\Entities\AssignmentDate() : new \App\Entities\AbsentDetail();

                        $line = $isAssignment ? (new M_AssignmentDetail($this->request))->find((new M_AssignmentDate($this->request))->find($post['id'])->trx_assignment_detail_id)
                            : (new M_AbsentDetail($this->request))->find($post['id']);

                        $row = $isAssignment ? (new M_Assignment($this->request))->find($line->trx_assignment_id)
                            : (new M_Absent($this->request))->find($line->trx_absent_id);

                        if (empty($leaveTypeId)) {
                            $this->entity->isagree = $isAgree;
                            // $response = $this->save();

                            if ($submissionForm == "Penugasan") {
                                $this->entity->realization_in = date('Y-m-d', strtotime($post['submissiondate'])) . " " . $post['starttime_att'];
                                $this->entity->realization_out = date('Y-m-d', strtotime($post['submissiondate'])) . " " . $post['endtime_att'];

                                if (empty($post['starttime_att']) || empty($post['endtime_att'])) {

                                    $clock_in = null;
                                    $clock_out = null;

                                    if (empty($post['starttime_att'])) {
                                        $whereIn = " v_attendance_serialnumber.md_employee_id = {$line->md_employee_id}";
                                        $whereIn .= " AND v_attendance_serialnumber.date = '{$submissionDate}'";
                                        $whereIn .= " AND v_attendance_serialnumber.clock_in IS NOT null";
                                        $whereIn .= " AND md_attendance_machines.md_branch_id != {$post['branch_in']}";
                                        $clock_in = $mAttendance->getAttendanceBranch($whereIn)->getRow();
                                    }

                                    if (empty($post['endtime_att'])) {
                                        $whereOut = " v_attendance_serialnumber.md_employee_id = {$line->md_employee_id}";
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
                            $list = $this->model->where('trx_absent_id', $line->trx_absent_id)->findAll();
                            $arr = array_map(fn($row) => [
                                "trx_absent_detail_id" => $row->trx_absent_detail_id,
                                "isagree" => "Y",
                                "updated_by" => $this->session->get('sys_user_id')
                            ], $list);

                            $this->model->builder->updateBatch($arr, $this->model->primaryKey);
                            $response = message('success', true, notification("updated"));
                        }
                    }

                    if ($isAgree === $notAgree) {

                        $line = $isAssignment ? (new M_AssignmentDetail($this->request))->find((new M_AssignmentDate($this->request))->find($post['foreignkey'])->trx_assignment_detail_id)
                            : (new M_AbsentDetail($this->request))->find($post['foreignkey']);
                        $row = $isAssignment ? (new M_Assignment($this->request))->find($line->trx_assignment_id)
                            : (new M_Absent($this->request))->find($line->trx_absent_id);

                        if ($post['submissiontype'] !== 'tidak setuju') {
                            $this->model = new M_Absent($this->request);
                            $this->entity = new \App\Entities\Absent();

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

                            $employee = $isAssignment ? (new M_Employee($this->request))->find($line->md_employee_id) : $row;

                            $this->entity->setEmployeeId($employee->getEmployeeId());
                            $this->entity->setNik($employee->getNik());


                            $this->entity->setBranchId($row->getBranchId());
                            $this->entity->setDivisionId($row->getDivisionId());
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

                            $docNo = $this->model->getInvNumber("submissiontype", $submissionType, $post);
                            $this->entity->setDocumentNo($docNo);
                            $this->isNewRecord = true;

                            $response = $this->save();

                            //* Foreignkey id 
                            $ID =  $this->insertID;

                            $this->model = new M_AbsentDetail($this->request);
                            $this->entity = new \App\Entities\AbsentDetail();

                            $this->entity->isagree = $holdAgree;
                            $this->entity->trx_absent_id = $ID;
                            $this->entity->lineno = 1;
                            $this->entity->date = $submissionDate;
                            $this->save();

                            //* Foreignkey id
                            $lineID = $this->insertID;

                            /**
                             * Update Pengajuan lama
                             */
                            $this->isNewRecord = false;

                            $this->model = $isAssignment ? new M_AssignmentDate($this->request) : new M_AbsentDetail($this->request);
                            $this->entity = $isAssignment ? new \App\Entities\AssignmentDate() : new \App\Entities\AbsentDetail();
                            $this->entity->isagree = $isAgree;
                            $this->entity->{$isAssignment ? 'trx_assignment_date_id' : 'trx_absent_detail_id'} = $post['foreignkey'];
                            $this->entity->table = 'trx_absent_detail';
                            $this->entity->{$isAssignment ? 'reference_id' : 'ref_absent_detail_id'} = $lineID;
                            $this->save();

                            /**
                             * Update Pengajuan ref absent detail
                             */
                            $this->model = new M_AbsentDetail($this->request);
                            $this->entity = new \App\Entities\AbsentDetail();
                            $this->entity->ref_absent_detail_id = $post['foreignkey'];
                            $this->entity->isagree = $agree;
                            $this->entity->table = $isAssignment ? 'trx_assignment_date' : 'trx_absent_detail';
                            $this->entity->trx_absent_detail_id = $lineID;
                            $this->save();

                            $this->model = new M_Absent($this->request);
                            $this->entity = new \App\Entities\Absent();
                            $this->entity->setDocStatus($this->DOCSTATUS_Completed);
                            $this->entity->setAbsentId($ID);
                            $this->save();
                        } else {
                            $this->model = $isAssignment ? new M_AssignmentDate($this->request) : new M_AbsentDetail($this->request);
                            $this->entity = $isAssignment ? new \App\Entities\AssignmentDate() : new \App\Entities\AbsentDetail();
                            $this->entity->isagree = $isAgree;
                            $this->entity->{$isAssignment ? 'trx_assignment_date_id' : 'trx_absent_detail_id'} = $post['foreignkey'];
                            $response = $this->save();
                        }
                    }

                    if ($isAssignment) {
                        $mAssignment = new M_Assignment($this->request);

                        $where = "trx_assignment.trx_assignment_id = {$line->trx_assignment_id}";
                        $where .= " AND trx_assignment_date.isagree IN ('M','H')";
                        $subLineData = $mAssignment->getDetailData($where)->getRow();

                        if (is_null($subLineData)) {
                            $this->model = new M_Assignment($this->request);
                            $this->entity = new \App\Entities\Assignment();

                            $this->entity->setAssignmentId($row->trx_assignment_id);
                            $this->entity->setDocStatus($this->DOCSTATUS_Completed);
                            $this->entity->setReceivedDate($today);
                            $this->save();
                        }
                    } else {
                        $this->model = new M_AbsentDetail($this->request);
                        $list = $this->model->where([
                            'isagree'       => $holdAgree,
                            'trx_absent_id' => $line->trx_absent_id
                        ])->first();

                        if (is_null($list)) {
                            $this->model = new M_Absent($this->request);
                            $this->entity = new \App\Entities\Absent();

                            $this->entity->setDocStatus($this->DOCSTATUS_Completed);
                            $this->entity->setReceivedDate($todayTime);
                            $this->entity->setAbsentId($line->trx_absent_id);
                            $this->save();
                        }
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
        $mEmpBenefit = new M_EmpBenefit($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            $agree = 'Y';
            $notAgree = 'N';
            $holdAgree = 'H';
            $today = date('Y-m-d');
            $todayTime = date('Y-m-d H:i:s');

            $isAgree = $post['isagree'];

            try {
                if (!$this->validation->run($post, 'realisasi_lembur_agree') && $isAgree === 'Y') {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $this->model = new M_OvertimeDetail($this->request);
                    $this->entity = new \App\Entities\OvertimeDetail();

                    $line = $this->model->find($post['id']);

                    $isOvertime = $mEmpBenefit->where(["md_employee_id" => $line->md_employee_id, "benefit" => "Lembur"])->first();

                    if ($isAgree === $agree) {

                        $startdate = date('Y-m-d', strtotime($line->startdate)) . " " . $post['starttime'];
                        $enddate = date('Y-m-d', strtotime($post["enddate_realization"])) . " " . $post['endtime_realization'];

                        if ($isOvertime && $isOvertime->status === 'Y') {
                            $ovt = $this->getHourOvertime($startdate, $enddate, $line->md_employee_id);
                        }

                        $this->entity->trx_overtime_detail_id = $post['id'];
                        $this->entity->startdate = $startdate;
                        $this->entity->enddate_realization = $enddate;
                        $this->entity->status = $isAgree;
                        $this->entity->overtime_expense = isset($ovt) ? $ovt['expense'] : null;
                        $this->entity->overtime_balance = isset($ovt) ? $ovt['balance'] : null;
                        $this->entity->total = isset($ovt) ? $ovt['total'] : null;

                        $response = $this->save();
                    }

                    if ($isAgree === $notAgree) {

                        $this->entity->trx_overtime_detail_id = $post['id'];
                        $this->entity->description = $post['description'];
                        $this->entity->status = $isAgree;

                        $response = $this->save();
                    }

                    $list = $this->model->where([
                        'status'       => $holdAgree,
                        'trx_overtime_id' => $line->trx_overtime_id
                    ])->first();

                    if (is_null($list)) {
                        $mOvertime = new M_Overtime($this->request);
                        $oEntity = new \App\Entities\Overtime();
                        $oEntity->setReceivedDate($todayTime);
                        $oEntity->setOvertimeId($line->trx_overtime_id);
                        $oEntity->setDocStatus($this->DOCSTATUS_Completed);
                        $mOvertime->save($oEntity);
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

            $agree = 'Y';
            $notAgree = 'N';
            $holdAgree = 'H';
            $today = date('Y-m-d');

            $isAgree = $post['isagree'];
            $submissionForm = $post['submissionform'];
            $typeFormAssignment = ['Tugas Kantor', 'Penugasan'];

            try {
                if ($isAgree === 'Y' && (in_array($submissionForm, $typeFormAssignment) ? false : !$this->validation->run($post, 'realisasi_kehadiran'))) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else if (date('Y-m-d', strtotime($post['submissiondate'])) > $today) {
                    $response = message('success', false, 'tanggal realisasi belum terpenuhi');
                } else {
                    if (in_array($submissionForm, $typeFormAssignment)) {
                        $mAssignment = new M_Assignment($this->request);
                        $mAssignmentDetail = new M_AssignmentDetail($this->request);
                        $this->model = new M_AssignmentDate($this->request);
                        $this->entity = new \App\Entities\AssignmentDate();

                        $id = $this->model->find($post['id']);

                        $this->entity->trx_assignment_date_id = $post['id'];

                        if ($isAgree === $agree) {
                            $this->entity->comment = $post['comment'];
                            $this->entity->isagree = $holdAgree;

                            if ($submissionForm === "Penugasan") {
                                $this->entity->branch_in = $post['branch_in'];
                                $this->entity->branch_out = $post['branch_out'];
                                $this->entity->realization_in = date('Y-m-d', strtotime($post['submissiondate'])) . " " . $post['starttime_att'];
                                $this->entity->realization_out = date('Y-m-d', strtotime($post['submissiondate'])) . " " . $post['endtime_att'];
                            }
                        } else {
                            $this->entity->isagree = $notAgree;
                            $this->entity->description = $post['description'];
                        }

                        $response = $this->save();


                        $lineData = $mAssignmentDetail->find($id->trx_assignment_detail_id);

                        $where = "trx_assignment.trx_assignment_id = {$lineData->trx_assignment_id}";
                        $where .= " AND trx_assignment_date.isagree IN ('M','H')";
                        $subLineData = $mAssignment->getDetailData($where)->getRow();

                        if (is_null($subLineData)) {
                            $headData = $mAssignment->find($lineData->trx_assignment_id);

                            $this->model = new M_Assignment($this->request);
                            $this->entity = new \App\Entities\Assignment();

                            $this->entity->setAssignmentId($headData->trx_assignment_id);
                            $this->entity->setDocStatus($this->DOCSTATUS_Completed);
                            $this->entity->setReceivedDate($today);
                            $this->save();
                        }
                    } else {
                        $mAbsent = new M_Absent($this->request);
                        $this->model = new M_AbsentDetail($this->request);
                        $this->entity = new \App\Entities\AbsentDetail();

                        $id = $this->model->find($post['id']);

                        $list = $mAbsent->where('trx_absent_id', $id->trx_absent_id)->first();

                        if ($isAgree === $agree) {
                            $this->model = new M_AbsentDetail($this->request);
                            $this->entity = new \App\Entities\AbsentDetail();

                            $enddate =   date('Y-m-d', strtotime($post["enddate_realization"])) . " " . $post['endtime_realization'];
                            $this->entity->trx_absent_detail_id = $post['id'];
                            $this->entity->date = $enddate;
                            $this->entity->isagree = $isAgree;
                            $response = $this->save();
                        }

                        if ($isAgree === $notAgree) {
                            $this->model = new M_AbsentDetail($this->request);
                            $this->entity = new \App\Entities\AbsentDetail();
                            $this->entity->trx_absent_detail_id = $post['id'];
                            $this->entity->isagree = $isAgree;

                            $response = $this->save();
                        }

                        $this->model = new M_Absent($this->request);
                        $this->entity = new \App\Entities\Absent();

                        $this->entity->setAbsentId($list->trx_absent_id);
                        $this->entity->setDocStatus($this->DOCSTATUS_Completed);
                        $this->entity->setReceivedDate($today);

                        if ($isAgree === $agree)
                            $this->entity->setEndDateRealization($enddate);

                        $this->save();
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
                            'id'    => 'lupa absen masuk',
                            'name'  => 'Lupa Absen Masuk'
                        ],
                        [
                            'id'    => 'lupa absen pulang',
                            'name'  => 'Lupa Absen Pulang'
                        ],
                        [
                            'id'    => 'datang terlambat',
                            'name'  => 'Datang Terlambat'
                        ],
                        [
                            'id'    => 'pulang cepat',
                            'name'  => 'Pulang Cepat'
                        ],
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
                } else 
                if (!empty($post['name']) && $post['name'] === "Ijin") {
                    $list = [
                        [
                            'id'    => 'alpa',
                            'name'  => 'Alpa'
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
            'validfrom <='      => $today
        ])->orderBy('validfrom', 'ASC')->first();

        $dayName = strtoupper(formatDay_idn($day));

        if (isset($workDay) && !is_null($workDay)) {
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
        if (isset($work)) {
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
        } catch (\Exception $e) {
            $response = message('error', false, $e->getMessage());
        }

        return $this->response->setJSON($response);
    }

    public function doApprovedRealization()
    {
        $absent = $this->model
            ->where([
                'docstatus'  => "$this->DOCSTATUS_Inprogress",
                'isapproved' => 'Y'
            ])
            ->groupStart()
            ->where('approveddate IS NULL')
            ->where('ADDDATE(updated_at, INTERVAL 2 DAY) <= NOW()')
            ->orGroupStart()
            ->where('approveddate IS NOT NULL')
            ->where('ADDDATE(approveddate, INTERVAL 2 DAY) <= NOW()')
            ->groupEnd()
            ->groupEnd()
            ->findAll();

        if ($absent) {
            $this->session->set([
                'sys_user_id'       => 100000,
            ]);

            $mAbsentDetail = new M_AbsentDetail($this->request);
            $todayTime = date('Y-m-d H:i:s');

            $absentIds = array_column($absent, 'trx_absent_id');

            $absentDetail = $mAbsentDetail->where('isagree', 'H')
                ->whereIn('trx_absent_id', $absentIds)
                ->findAll();

            $arr = [];

            foreach ($absentDetail as $row) {
                $arr[] = [
                    "trx_absent_detail_id"  => $row->trx_absent_detail_id,
                    "trx_absent_id"         => $row->trx_absent_id,
                    "isagree"               => "Y",
                    "updated_at"            => $todayTime,
                    "updated_by"            => $this->session->get('sys_user_id')
                ];
            }

            $result = $mAbsentDetail->builder->updateBatch($arr, $mAbsentDetail->primaryKey);

            if ($result > 0) {
                $this->entity = new \App\Entities\Absent();

                foreach ($absentIds as $id) {
                    $this->entity->setDocStatus($this->DOCSTATUS_Completed);
                    $this->entity->setReceivedDate($todayTime);
                    $this->entity->setAbsentId($id);
                    $this->save();
                }
            }
        }
    }
}

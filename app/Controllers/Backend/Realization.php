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
use App\Models\M_Attend;
use App\Models\M_Employee;
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
            $table = $this->model->table;
            $select = $this->model->getSelectDetail();
            $join = $this->model->getJoinDetail();
            $order = [
                '', // Number
                'trx_absent_detail.date',
                'md_doctype.name',
                'md_branch.name',
                'md_division.name',
                'md_employee.fullname',
                'trx_absent.reason',
                'trx_absent.docstatus',
                'sys_user.name'
            ];
            $search = $this->request->getPost('search');
            $sort = ['trx_absent_detail.date' => 'ASC', 'md_employee.fullname' => 'ASC'];

            $formType = [
                $this->model->Pengajuan_Lupa_Absen_Masuk,
                $this->model->Pengajuan_Lupa_Absen_Pulang,
                $this->model->Pengajuan_Datang_Terlambat,
                $this->model->Pengajuan_Pulang_Cepat,
            ];

            $where = [
                "trx_absent.docstatus = '{$this->DOCSTATUS_Inprogress}' 
                AND trx_absent_detail.isagree = 'H' 
                AND trx_absent.submissiontype NOT IN (" . implode(",", $formType) . ")"
            ];

            $data = [];

            $fieldChk = new \App\Entities\Table();
            $fieldChk->setName("ischecked");
            $fieldChk->setType("checkbox");
            $fieldChk->setClass("check-realize");

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);
            // $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_absent_detail_id;

                $number++;

                $reason = $value->reason;

                if ($value->comment)
                    $reason .= " | <small class='text-danger'>{$value->comment}</small>";

                // $row[] = $this->field->fieldTable($fieldChk);
                $row[] = $number;
                $row[] = format_dmy($value->date, '-');
                $row[] = $value->doctype;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = $value->employee_fullname;
                $row[] = viewImage($value->trx_absent_id, $value->image);
                $row[] = $reason;
                $row[] = $this->template->tableButtonProcess($ID);
                $data[] = $row;
            endforeach;

            // $recordsTotal = count($data);
            // $recordsFiltered = count($data);

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
        $mAccess = new M_AccessMenu($this->request);
        $mAttendance = new M_Attend($this->request);
        $mEmployee = new M_Employee($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelectDetail();
            $join = $this->model->getJoinDetail();
            $order = [
                '', // Number
                'trx_absent_detail.date',
                'md_doctype.name',
                'md_branch.name',
                'md_division.name',
                'md_employee.fullname',
                'trx_absent.reason',
                'trx_absent.docstatus',
                'sys_user.name'
            ];
            $search = $this->request->getPost('search');
            $sort = ['trx_absent_detail.date' => 'ASC', 'md_employee.fullname' => 'ASC'];

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

                    $where['trx_absent.md_employee_id'] = [
                        'value'     => $arrMerge
                    ];
                } else if (!$roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $where['trx_absent.md_employee_id'] = [
                        'value'     => $arrEmployee
                    ];
                } else if ($roleEmp && empty($this->session->get('md_employee_id'))) {
                    $where['trx_absent.md_employee_id'] = [
                        'value'     => $arrEmpBased
                    ];
                } else {
                    $where['trx_absent.md_employee_id'] = $this->session->get('md_employee_id');
                }
            } else if (!empty($this->session->get('md_employee_id'))) {
                $where['trx_absent.md_employee_id'] = [
                    'value'     => $arrEmployee
                ];
            } else {
                $where['trx_absent.md_employee_id'] = $this->session->get('md_employee_id');
            }

            $where['trx_absent.docstatus'] = $this->DOCSTATUS_Inprogress;
            $where['trx_absent_detail.isagree'] = 'H';
            $typeForm = [$this->model->Pengajuan_Lupa_Absen_Masuk, $this->model->Pengajuan_Lupa_Absen_Pulang, $this->model->Pengajuan_Datang_Terlambat, $this->model->Pengajuan_Pulang_Cepat];
            $where['trx_absent.submissiontype'] = ['value' => $typeForm];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            $data = [];
            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_absent_detail_id;
                $tanggal = '';
                $clock = '';

                $startDate = date('Y-m-d', strtotime($value->startdate));

                $whereClause = "v_attendance.md_employee_id = {$value->md_employee_id}";
                $whereClause .= " AND v_attendance.date = '{$startDate}'";

                if ($value->submissiontype == $this->model->Pengajuan_Pulang_Cepat)
                    $whereClause .= " AND v_attendance.clock_out IS NOT NULL";
                else if ($value->submissiontype == $this->model->Pengajuan_Datang_Terlambat)
                    $whereClause .= " AND v_attendance.clock_in IS NOT null";

                $attendance = null;

                if ($whereClause)
                    $attendance = $mAttendance->getAttendance($whereClause)->getRow();

                if ($attendance && $value->submissiontype == $this->model->Pengajuan_Pulang_Cepat) {
                    $tanggal = format_dmy($attendance->date, '-');
                    $clock = format_time($attendance->clock_in);
                } else if ($attendance && $value->submissiontype == $this->model->Pengajuan_Datang_Terlambat) {
                    $tanggal = format_dmy($attendance->date, '-');
                    $clock = format_time($attendance->clock_out);
                }

                $number++;

                $row[] = $number;
                $row[] = format_dmy($value->date, '-');
                $row[] = format_time($value->date);
                $row[] = $value->documentno;
                $row[] = $value->doctype;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = $value->employee_fullname;
                $row[] = isset($tanggal) ? $tanggal : '';
                $row[] = isset($clock) ? $clock : '';
                $row[] = $value->reason;
                $row[] = $this->template->tableButtonProcess($ID, true);
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
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            $agree = 'Y';
            $notAgree = 'N';
            $holdAgree = 'H';

            $isAgree = $post['isagree'];
            $submissionDate = $post['submissiondate'];
            $today = date('Y-m-d');
            $todayTime = date('Y-m-d H:i:s');
            $leaveTypeId = $post['md_leavetype_id'];

            try {
                if (!$this->validation->run($post, 'realisasi_agree') && $isAgree === 'Y') {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else if (!$this->validation->run($post, 'realisasi_not_agree') && $isAgree === 'N') {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    if ($isAgree === $agree) {
                        $this->model = new M_AbsentDetail($this->request);
                        $this->entity = new \App\Entities\AbsentDetail();

                        $line = $this->model->find($post['id']);

                        if (empty($leaveTypeId)) {
                            $this->entity->isagree = $isAgree;
                            $response = $this->save();
                        } else {
                            $list = $this->model->where('trx_absent_id', $line->trx_absent_id)->findAll();

                            $arr = [];

                            foreach ($list as $row) {
                                $arr[] = [
                                    "trx_absent_detail_id" => $row->trx_absent_detail_id,
                                    "isagree"           => "Y",
                                    "updated_by"        => $this->session->get('sys_user_id')
                                ];
                            }

                            $this->model->builder->updateBatch($arr, $this->model->primaryKey);
                            $this->message = notification("updated");
                            $response = message('success', true, $this->message);
                        }
                    }

                    if ($isAgree === $notAgree) {
                        $this->model = new M_AbsentDetail($this->request);
                        $line = $this->model->find($post['foreignkey']);

                        $this->model = new M_Absent($this->request);
                        $this->entity = new \App\Entities\Absent();

                        $row = $this->model->find($line->trx_absent_id);

                        /**
                         * Insert Pengajuan baru
                         */
                        $necessary = '';
                        if ($post['submissiontype'] === 'ijin') {
                            $necessary = 'IJ';
                            $submissionType = $this->model->Pengajuan_Ijin;
                            $this->entity->setNecessary($necessary);
                            $this->entity->setSubmissionType($submissionType);
                        }

                        if ($post['submissiontype'] === 'alpa') {
                            $necessary = 'AL';
                            $submissionType = $this->model->Pengajuan_Alpa;
                            $this->entity->setNecessary($necessary);
                            $this->entity->setSubmissionType($submissionType);
                        }

                        $this->entity->setEmployeeId($row->getEmployeeId());
                        $this->entity->setNik($row->getNik());
                        $this->entity->setBranchId($row->getBranchId());
                        $this->entity->setDivisionId($row->getDivisionId());
                        $this->entity->setReceivedDate($todayTime);
                        $this->entity->setReason($post['reason']);
                        $this->entity->setSubmissionDate($today);
                        $this->entity->setStartDate(date('Y-m-d', strtotime($submissionDate)));
                        $this->entity->setEndDate(date('Y-m-d', strtotime($submissionDate)));
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
                        $this->entity->date = date('Y-m-d', strtotime($submissionDate));
                        $this->save();

                        //* Foreignkey id
                        $lineID = $this->insertID;

                        /**
                         * Update Pengajuan lama
                         */
                        $this->isNewRecord = false;

                        $this->model = new M_AbsentDetail($this->request);
                        $this->entity = new \App\Entities\AbsentDetail();
                        $this->entity->isagree = $isAgree;
                        $this->entity->trx_absent_detail_id = $post['foreignkey'];
                        $this->entity->ref_absent_detail_id = $lineID;
                        $this->save();

                        /**
                         * Update Pengajuan ref absent detail
                         */
                        $this->model = new M_AbsentDetail($this->request);
                        $this->entity = new \App\Entities\AbsentDetail();
                        $this->entity->ref_absent_detail_id = $post['foreignkey'];
                        $this->entity->isagree = $agree;
                        $this->entity->trx_absent_detail_id = $lineID;
                        $this->save();

                        $this->model = new M_Absent($this->request);
                        $this->entity = new \App\Entities\Absent();
                        $this->entity->setDocStatus($this->DOCSTATUS_Completed);
                        $this->entity->setAbsentId($ID);
                        $this->save();
                    }

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
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
            // return json_encode($response);
        }
    }

    public function createOvertime()
    {
        $mEmployee = new M_Employee($this->request);

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

                    $isOvertime = $mEmployee->find($line->md_employee_id);

                    if ($isAgree === $agree) {

                        $startdate = date('Y-m-d', strtotime($line->startdate)) . " " . $post['starttime'];
                        $enddate = date('Y-m-d', strtotime($post["enddate_realization"])) . " " . $post['endtime_realization'];

                        if ($isOvertime->isOvertime === 'Y') {
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

            try {
                if (!$this->validation->run($post, 'realisasi_kehadiran') && $isAgree === 'Y') {
                    $response = $this->field->errorValidation($this->model->table, $post);
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

                    // if ($isAgree === $notAgree) {
                    //     $this->model = new M_AbsentDetail($this->request);
                    //     $this->entity = new \App\Entities\AbsentDetail();
                    //     $this->entity->trx_absent_detail_id = $post['id'];
                    //     $this->entity->isagree = $isAgree;

                    //     $response = $this->save();
                    // }

                    $mAbsent = new M_Absent($this->request);
                    $aEntity = new \App\Entities\Absent();

                    $aEntity->setAbsentId($list->trx_absent_id);
                    $aEntity->setDocStatus($this->DOCSTATUS_Completed);
                    $aEntity->setReceivedDate($today);
                    $aEntity->setEndDateRealization($enddate);
                    $mAbsent->save($aEntity);
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }
            return $this->response->setJSON($response);

            // return json_encode($post);
        }
    }

    public function getList()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->getVar();

            $response = [];

            try {
                // if (isset($post['search'])) {
                //     $list = $this->model->where('isactive', 'Y')
                //         ->like('name', $post['search'])
                //         ->orderBy('name', 'ASC')
                //         ->findAll();
                // } else {
                //     $list = $this->model->where('isactive', 'Y')
                //         ->orderBy('name', 'ASC')
                //         ->findAll();
                // }

                $list = [
                    [
                        'id'    => 'alpa',
                        'name'  => 'alpa'
                    ],
                    [
                        'id'    => 'ijin',
                        'name'  => 'ijin'
                    ],
                ];

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
}

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
use App\Models\M_AssignmentDetail;
use App\Models\M_EmpBenefit;
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
        $mAbsentDetail = new M_AbsentDetail($this->request);
        $mAssignment = new M_Assignment($this->request);
        $mAssignmentDate = new M_AssignmentDate($this->request);

        if ($this->request->getMethod(true) === 'POST') {

            //** This for Getting data from table Trx Absent */
            $order = [
                '', // Number
                'trx_absent_detail.date',
                '',
                'trx_absent.documentno',
                'md_doctype.name',
                'md_branch.name',
                'md_division.name',
                'md_employee.fullname',
                '',
                '',
                'trx_absent.reason'
            ];
            $search = $this->request->getPost('search');
            $sort = ['trx_absent_detail.date' => 'ASC', 'md_employee.fullname' => 'ASC'];
            $typeFormAbsent = [$this->model->Pengajuan_Lupa_Absen_Masuk, $this->model->Pengajuan_Lupa_Absen_Pulang, $this->model->Pengajuan_Datang_Terlambat, $this->model->Pengajuan_Pulang_Cepat];

            $result1 = $this->getTransactionData($this->model, $order, $sort, $search, $typeFormAbsent, $mAbsentDetail);

            $orderAssignment = [
                '', // Number
                'trx_assignment_date.date',
                '',
                'trx_assignment.documentno',
                'md_doctype.name',
                'md_branch.name',
                'md_division.name',
                'md_employee.fullname',
                '',
                '',
                'trx_assignment.description'
            ];
            $sortAssignment = ['trx_assignment_date.date' => 'ASC', 'md_employee.fullname' => 'ASC'];

            $result2 = $this->getTransactionData(
                $mAssignment,
                $orderAssignment,
                $sortAssignment,
                $search,
                null,
                null,
                $mAssignmentDate
            );

            $result = [
                'draw'              => $result1['draw'],
                'recordsTotal'      => $result1['recordsTotal'] + $result2['recordsTotal'],
                'recordsFiltered'   => $result1['recordsFiltered'] + $result2['recordsFiltered'],
                'data'              => array_merge($result1['data'], $result2['data'])
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
            $submissionForm = $post['submissionform'];
            $typeFormAssignment = ['Tugas Kantor', 'Penugasan'];

            try {
                if (!$this->validation->run($post, 'realisasi_agree') && $isAgree === 'Y') {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else if (!$this->validation->run($post, 'realisasi_not_agree') && $isAgree === 'N') {
                    $response = $this->field->errorValidation($this->model->table, $post);
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
                            $response = $this->save();
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

                        //** This is Oldways*/
                        // if ($isAssignment) {
                        //     $mAssignment = new M_Assignment($this->request);
                        //     $mAssignmentDetail = new M_AssignmentDetail($this->request);
                        //     $mAssignmentDate = new M_AssignmentDate($this->request);

                        //     $subLine = $mAssignmentDate->find($post['foreignkey']);
                        //     $line = $mAssignmentDetail->find($subLine->trx_assignment_detail_id);
                        //     $row = $mAssignment->find($line->trx_assignment_id);
                        // } else {
                        //     $mAbsent = new M_Absent($this->request);
                        //     $mAbsentDetail = new M_AbsentDetail($this->request);

                        //     $line = $mAbsentDetail->find($post['foreignkey']);
                        //     $row = $mAbsent->find($line->trx_absent_id);
                        // }

                        //** This is Newways*/
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

                            //** This is Oldways*/
                            // $necessary = '';
                            // if ($post['submissiontype'] === 'ijin') {
                            //     $necessary = 'IJ';
                            //     $submissionType = $this->model->Pengajuan_Ijin;
                            //     $this->entity->setNecessary($necessary);
                            //     $this->entity->setSubmissionType($submissionType);
                            // }

                            // if ($post['submissiontype'] === 'alpa') {
                            //     $necessary = 'AL';
                            //     $submissionType = $this->model->Pengajuan_Alpa;
                            //     $this->entity->setNecessary($necessary);
                            //     $this->entity->setSubmissionType($submissionType);
                            // }

                            // if ($post['submissiontype'] === 'lupa absen masuk') {
                            //     $necessary = 'LM';
                            //     $submissionType = $this->model->Pengajuan_Lupa_Absen_Masuk;
                            //     $this->entity->setNecessary($necessary);
                            //     $this->entity->setSubmissionType($submissionType);
                            // }

                            // if ($post['submissiontype'] === 'datang terlambat') {
                            //     $necessary = 'DT';
                            //     $submissionType = $this->model->Pengajuan_Datang_Terlambat;
                            //     $this->entity->setNecessary($necessary);
                            //     $this->entity->setSubmissionType($submissionType);
                            // }

                            //** This is Newways*/
                            $necessaryMap = [
                                'ijin' => ['IJ', $this->model->Pengajuan_Ijin],
                                'alpa' => ['AL', $this->model->Pengajuan_Alpa],
                                'lupa absen masuk' => ['LM', $this->model->Pengajuan_Lupa_Absen_Masuk],
                                'datang terlambat' => ['DT', $this->model->Pengajuan_Datang_Terlambat]
                            ];

                            if (isset($necessaryMap[$post['submissiontype']])) {
                                [$necessary, $submissionType] = $necessaryMap[$post['submissiontype']];
                                $this->entity->setNecessary($necessary);
                                $this->entity->setSubmissionType($submissionType);
                            }

                            //** This is Oldways*/
                            // if ($isAssignment) {
                            //     $mEmployee = new M_Employee($this->request);
                            //     $employee = $mEmployee->find($line->md_employee_id);
                            //     $this->entity->setEmployeeId($employee->getEmployeeId());
                            //     $this->entity->setNik($employee->getNik());
                            // } else {
                            //     $this->entity->setEmployeeId($row->getEmployeeId());
                            //     $this->entity->setNik($row->getNik());
                            // }

                            //** This is Newways */
                            $employee = $isAssignment ? (new M_Employee($this->request))->find($line->md_employee_id) : $row;

                            $this->entity->setEmployeeId($employee->getEmployeeId());
                            $this->entity->setNik($employee->getNik());


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

                            //** This is Oldways*/
                            // if ($isAssignment) {
                            //     $this->model = new M_AssignmentDate($this->request);
                            //     $this->entity = new \App\Entities\AssignmentDate();
                            //     $this->entity->isagree = $isAgree;
                            //     $this->entity->trx_assignment_date_id = $post['foreignkey'];
                            //     $this->entity->reference_id = $lineID;
                            //     $this->save();
                            // } else {
                            //     $this->model = new M_AbsentDetail($this->request);
                            //     $this->entity = new \App\Entities\AbsentDetail();
                            //     $this->entity->isagree = $isAgree;
                            //     $this->entity->trx_absent_detail_id = $post['foreignkey'];
                            //     $this->entity->ref_absent_detail_id = $lineID;
                            //     $this->save();
                            // }

                            //** This is Newways*/
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
                        $subLineData = $mAssignment->checkStatusDate($where)->getRow();

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
                if (
                    in_array($submissionForm, $typeFormAssignment) ? false :
                    !$this->validation->run($post, 'realisasi_kehadiran') && $isAgree === 'Y'
                ) {
                    $response = $this->field->errorValidation($this->model->table, $post);
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
                        } else {
                            $this->entity->isagree = $notAgree;
                            $this->entity->description = $post['description'];
                        }

                        $response = $this->save();


                        $lineData = $mAssignmentDetail->find($id->trx_assignment_detail_id);

                        $where = "trx_assignment.trx_assignment_id = {$lineData->trx_assignment_id}";
                        $where .= " AND trx_assignment_date.isagree IN ('M','H')";
                        $subLineData = $mAssignment->checkStatusDate($where)->getRow();

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
                // if (!empty($post['name']) && $post['name'] === "Tugas Kantor") {
                //     $list = [
                //         [
                //             'id'    => 'lupa absen masuk',
                //             'name'  => 'Lupa Absen Masuk'
                //         ],
                //         [
                //             'id'    => 'datang terlambat',
                //             'name'  => 'Datang Terlambat'
                //         ],
                //     ];
                // } else 
                if (!empty($post['name']) && $post['name'] === "Ijin") {
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

    private function getTransactionData($model, $order, $sort, $search, $typeForm = null, $modelDetail = null, $modelSubDetail = null)
    {
        $mAttendance = new M_Attendance($this->request);
        $mAccess = new M_AccessMenu($this->request);
        $mEmployee = new M_Employee($this->request);

        $table = $model->table;
        $select = $model->getSelectDetail();
        $join = $model->getJoinDetail();

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

                $where[$table . '.md_employee_id'] = [
                    'value'     => $arrMerge
                ];
            } else if (!$roleEmp && !empty($this->session->get('md_employee_id'))) {
                $where[$table . '.md_employee_id'] = [
                    'value'     => $arrEmployee
                ];
            } else if ($roleEmp && empty($this->session->get('md_employee_id'))) {
                $where[$table . '.md_employee_id'] = [
                    'value'     => $arrEmpBased
                ];
            } else {
                $where[$table . '.md_employee_id'] = $this->session->get('md_employee_id');
            }
        } else if (!empty($this->session->get('md_employee_id'))) {
            $where[$table . '.md_employee_id'] = [
                'value'     => $arrEmployee
            ];
        } else {
            $where[$table . '.md_employee_id'] = $this->session->get('md_employee_id');
        }

        $where[$table . '.docstatus'] = $this->DOCSTATUS_Inprogress;
        $where[$table . '.isapproved'] = 'Y';

        if ($modelSubDetail) {
            $where[$modelSubDetail->table . '.isagree'] = 'M';
        } else {
            $where[$modelDetail->table . '.isagree'] = 'H';
        }

        if ($typeForm)
            $where[$table . '.submissiontype'] = ['value' => $typeForm];

        $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

        $number = $this->request->getPost('start');
        $data = [];
        foreach ($list as $value) :
            $row = [];

            if ($modelSubDetail) {
                $ID = $value->{$modelSubDetail->primaryKey};
            } else {
                $ID = $value->{$modelDetail->primaryKey};
            }

            $tanggal = '';
            $clock = '';

            $startDate = date('Y-m-d', strtotime($value->startdate));

            $whereClause = "v_attendance.md_employee_id = {$value->employee_id}";
            $whereClause .= " AND v_attendance.date = '{$startDate}'";

            if ($value->submissiontype == $model->Pengajuan_Pulang_Cepat)
                $whereClause .= " AND v_attendance.clock_out IS NOT NULL";
            else if ($value->submissiontype == $model->Pengajuan_Datang_Terlambat)
                $whereClause .= " AND v_attendance.clock_in IS NOT null";

            $attendance = $mAttendance->getAttendance($whereClause)->getRow();

            if ($attendance && $value->submissiontype == $model->Pengajuan_Pulang_Cepat) {
                $tanggal = format_dmy($attendance->date, '-');
                $absent = format_time($attendance->clock_out);
                $clock = format_time($value->date);
            } else if ($attendance && $value->submissiontype == $model->Pengajuan_Datang_Terlambat) {
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

        return $result;
    }
}

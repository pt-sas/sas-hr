<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use App\Models\M_AccessMenu;
use App\Models\M_Employee;
use App\Models\M_AbsentDetail;
use App\Models\M_Holiday;
use App\Models\M_Attendance;
use App\Models\M_EmpWorkDay;
use App\Models\M_Rule;
use App\Models\M_WorkDetail;
use App\Models\M_LeaveBalance;
use App\Models\M_MassLeave;
use Config\Services;

class Leave extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Absent($this->request);
        $this->modelDetail = new M_AbsentDetail($this->request);
        $this->entity = new \App\Entities\Absent();
    }

    public function index()
    {
        $data = [
            'today'     => date('d-M-Y')
        ];

        return $this->template->render('transaction/leave/v_leave', $data);
    }

    public function showAll()
    {
        $mAccess = new M_AccessMenu($this->request);
        $mEmployee = new M_Employee($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelect();
            $join = $this->model->getJoin();
            $order = [
                '', // Hide column
                '', // Number column
                'trx_absent.documentno',
                'md_employee.fullname',
                'trx_absent.nik',
                'md_branch.name',
                'md_division.name',
                'trx_absent.submissiondate',
                'trx_absent.startdate',
                'trx_absent.receiveddate',
                'trx_absent.reason',
                'trx_absent.docstatus',
                'sys_user.name'
            ];
            $search = [
                'trx_absent.documentno',
                'md_employee.fullname',
                'trx_absent.nik',
                'md_branch.name',
                'md_division.name',
                'trx_absent.submissiondate',
                'trx_absent.startdate',
                'trx_absent.enddate',
                'trx_absent.receiveddate',
                'trx_absent.reason',
                'trx_absent.docstatus',
                'sys_user.name'
            ];
            $sort = ['trx_absent.submissiondate' => 'DESC'];

            /**
             * Hak akses
             */
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

            $where['trx_absent.submissiontype'] = $this->model->Pengajuan_Cuti;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_absent_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->documentno;
                $row[] = $value->employee_fullname;
                $row[] = $value->nik;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = format_dmy($value->startdate, '-') . " s/d " . format_dmy($value->enddate, '-');
                $row[] = !is_null($value->receiveddate) ? format_dmy($value->receiveddate, '-') : "";
                $row[] = $value->reason;
                $row[] = docStatus($value->docstatus);
                $row[] = $value->createdby;
                $row[] = $this->template->tableButton($ID, $value->docstatus);
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
        $mHoliday = new M_Holiday($this->request);
        $mRule = new M_Rule($this->request);
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);
        $mLeaveBalance = new M_LeaveBalance($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            $post["submissiontype"] = $this->model->Pengajuan_Cuti;
            $post["necessary"] = 'CT';
            $today = date('Y-m-d');
            $employeeId = $post['md_employee_id'];
            $day = date('w');

            try {
                $this->entity->fill($post);

                if (!$this->validation->run($post, 'leave')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $holidays = $mHoliday->getHolidayDate();
                    $startDate = $post['startdate'];
                    $endDate = $post['enddate'];
                    $nik = $post['nik'];
                    $submissionDate = $post['submissiondate'];
                    $subDate = date('Y-m-d', strtotime($submissionDate));

                    $rule = $mRule->where([
                        'name'      => 'Cuti',
                        'isactive'  => 'Y'
                    ])->first();

                    $minDays = $rule && !empty($rule->min) ? $rule->min : 1;
                    $maxDays = $rule && !empty($rule->max) ? $rule->max : 1;

                    //TODO : Get work day employee
                    $workDay = $mEmpWork->where([
                        'md_employee_id'    => $post['md_employee_id'],
                        'validfrom <='      => $today
                    ])->orderBy('validfrom', 'ASC')->first();

                    if (is_null($workDay)) {
                        $response = message('success', false, 'Hari kerja belum ditentukan');
                    } else {
                        //TODO : Get Work Detail
                        $whereClause = "md_work_detail.isactive = 'Y'";
                        $whereClause .= " AND md_employee_work.md_employee_id = $employeeId";
                        $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
                        $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getResult();

                        $daysOff = getDaysOff($workDetail);

                        $nextDate = lastWorkingDays($subDate, $holidays, $minDays, false, $daysOff);

                        //* last index of array from variable nextDate
                        $lastDate = end($nextDate);

                        //TODO : Get submission
                        $docStatus = [
                            $this->DOCSTATUS_Completed,
                            $this->DOCSTATUS_Drafted
                        ];

                        if (isset($post['id'])) {
                            $trx = $this->model->where([
                                'trx_absent.nik'            => $nik,
                                'trx_absent.startdate >='   => $startDate,
                                'trx_absent.enddate <='     => $endDate,
                                'trx_absent.trx_absent_id <>' => $post['id']
                            ])->whereIn('trx_absent.docstatus', $docStatus)->first();
                        } else {
                            $trx = $this->model->where([
                                'trx_absent.nik'            => $nik,
                                'trx_absent.startdate >='   => $startDate,
                                'trx_absent.enddate <='     => $endDate
                            ])->whereIn('trx_absent.docstatus', $docStatus)->first();
                        }

                        $addDays = lastWorkingDays($subDate, [], $maxDays, false, [], true);

                        //* last index of array from variable addDays
                        $addDays = end($addDays);

                        $endDate = date('Y-m-d', strtotime($endDate));
                        $startDate = date("Y-m-d", strtotime($startDate));

                        $dateRange = getDatesFromRange($startDate, $endDate, $holidays, 'Y-m-d', 'all', $daysOff);
                        $totalDays = count($dateRange);

                        $leaveBalance = $mLeaveBalance->where([
                            'year'              => date("Y", strtotime($startDate)),
                            'md_employee_id'    => $employeeId
                        ])->first();

                        if ($endDate > $addDays) {
                            $response = message('success', false, 'Tanggal selesai melewati tanggal ketentuan');
                        } else if ($startDate <= $lastDate) {
                            $response = message('success', false, 'Tidak bisa mengajukan pada tanggal ' . format_dmy($startDate, "-") . ', karena tidak sesuai dengan batas pengajuan');
                        } else if ($trx) {
                            $response = message('success', false, 'Tidak bisa mengajukan pada rentang tanggal, karena sudah ada pengajuan lain');
                        } else if ($leaveBalance->balance_amount == 0 || $totalDays > $leaveBalance->balance_amount) {
                            $response = message('success', false, 'Saldo cuti tidak cukup');
                        } else {
                            $this->entity->fill($post);

                            if ($this->isNew()) {
                                $this->entity->setDocStatus($this->DOCSTATUS_Drafted);
                                $docNo = $this->model->getInvNumber("submissiontype", $this->model->Pengajuan_Cuti, $post);
                                $this->entity->setDocumentNo($docNo);
                            }

                            $response = $this->save();
                        }
                    }
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function show($id)
    {
        $mEmployee = new M_Employee($this->request);

        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();
                $detail = $this->modelDetail->where($this->model->primaryKey, $id)->findAll();
                $rowEmp = $mEmployee->where($mEmployee->primaryKey, $list[0]->getEmployeeId())->first();

                $list = $this->field->setDataSelect($mEmployee->table, $list, $mEmployee->primaryKey, $rowEmp->getEmployeeId(), $rowEmp->getValue());

                $title = $list[0]->getDocumentNo() . "_" . $rowEmp->getFullName();

                //Need to set data into date field in form
                $list[0]->startdate = format_dmy($list[0]->startdate, "-");
                $list[0]->enddate = format_dmy($list[0]->enddate, "-");

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($title);
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setList($list);

                $result = [
                    'header'    => $this->field->store($fieldHeader),
                    'line'      => $this->tableLine('edit', $detail)
                ];

                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function destroy($id)
    {
        if ($this->request->isAJAX()) {
            try {
                $result = $this->delete($id);
                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function processIt()
    {
        $cWfs = new WScenario();
        $mLeaveBalance = new M_LeaveBalance($this->request);
        $mHoliday = new M_Holiday($this->request);
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);

        if ($this->request->isAJAX()) {
            $post = $this->request->getVar();

            $_ID = $post['id'];
            $_DocAction = $post['docaction'];

            $row = $this->model->find($_ID);
            $menu = $this->request->uri->getSegment(2);
            $today = date("Y-m-d");

            try {
                if (!empty($_DocAction)) {
                    if ($_DocAction === $row->getDocStatus()) {
                        $response = message('error', true, 'Silahkan refresh terlebih dahulu');
                    } else if ($_DocAction === $this->DOCSTATUS_Completed) {
                        $holidays = $mHoliday->getHolidayDate();

                        $workDay = $mEmpWork->where([
                            'md_employee_id'    => $row->md_employee_id,
                            'validfrom <='      => $today
                        ])->orderBy('validfrom', 'ASC')->first();

                        //TODO : Get Work Detail
                        $whereClause = "md_work_detail.isactive = 'Y'";
                        $whereClause .= " AND md_employee_work.md_employee_id = $row->md_employee_id";
                        $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
                        $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getResult();

                        $daysOff = getDaysOff($workDetail);

                        $endDate = date('Y-m-d', strtotime($row->enddate));
                        $startDate = date("Y-m-d", strtotime($row->startdate));

                        $dateRange = getDatesFromRange($startDate, $endDate, $holidays, 'Y-m-d', 'all', $daysOff);
                        $totalDays = count($dateRange);

                        $leaveBalance = $mLeaveBalance->where([
                            'year'              => date("Y", strtotime($startDate)),
                            'md_employee_id'    => $row->md_employee_id
                        ])->first();

                        if ($leaveBalance->balance_amount == 0 || $totalDays > $leaveBalance->balance_amount) {
                            $response = message('error', true, 'Saldo cuti tidak cukup');
                        } else {
                            $this->message = $cWfs->setScenario($this->entity, $this->model, $this->modelDetail, $_ID, $_DocAction, $menu, $this->session);
                            $response = message('success', true, true);
                        }
                    } else if ($_DocAction === $this->DOCSTATUS_Unlock) {
                        $this->entity->setDocStatus($this->DOCSTATUS_Drafted);
                        $response = $this->save();
                    } else if (($_DocAction === $this->DOCSTATUS_Unlock || $_DocAction === $this->DOCSTATUS_Voided)) {
                        $response = message('error', true, 'Tidak bisa diproses');
                    } else {
                        $this->entity->setDocStatus($_DocAction);
                        $response = $this->save();
                    }
                } else {
                    $response = message('error', true, 'Silahkan pilih tindakan terlebih dahulu.');
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function tableLine($set = null, $detail = [])
    {
        $table = [];

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $docNoRef = "";
                $line = $this->model->where('trx_absent_id', $row->trx_absent_id)->first();

                if (!empty($row->ref_absent_detail_id)) {
                    $lineRef = $this->modelDetail->getDetail('trx_absent_detail_id', $row->ref_absent_detail_id)->getRow();
                    $docNoRef = $lineRef->documentno;
                }

                $table[] = [
                    $row->lineno,
                    format_dmy($row->date, '-'),
                    $line->getDocumentNo(),
                    $docNoRef,
                    statusRealize($row->isagree)
                ];
            endforeach;
        }

        return json_encode($table);
    }

    public function indexGen()
    {
        $data = [
            'year' => date('Y')
        ];

        return $this->template->render('generate/leave/v_generate_leave', $data);
    }

    public function genShowAll()
    {
        $mLeaveBalance = new M_LeaveBalance($this->request);
        $mEmployee = new M_Employee($this->request);

        $post = $this->request->getVar();
        $data = [];

        $recordTotal = 0;
        $recordsFiltered = 0;

        if ($this->request->getMethod(true) === 'POST') {
            if (isset($post['form']) && $post['clear'] === 'false') {
                $table = $mLeaveBalance->table;
                $select = $mLeaveBalance->getSelect();
                $join = $mLeaveBalance->getJoin();
                $order = $this->request->getPost('columns');
                $search = $this->request->getPost('search');
                $sort = ['md_employee.value' => 'ASC'];

                $employee = [];
                foreach ($post['form'] as $value) {
                    if (!empty($value['value'])) {
                        if ($value['name'] === "md_employee_id") {
                            $employee = $value['value'];
                        }
                        if ($value['name'] === "year") {
                            $year = $value['value'];
                        }
                    }
                }

                $arrEmpID = implode(", ", array_map(function ($value) {
                    return $value;
                }, $employee));

                $prevYear = $year - 1;
                $subQuery = "NOT EXISTS (select 1
                                        FROM trx_leavebalance
                                        WHERE md_employee.md_employee_id = trx_leavebalance.md_employee_id
                                        AND trx_leavebalance.year = {$year})";

                if ($employee) {
                    $where = ["md_employee.isactive = 'Y'
                    AND md_employee.md_status_id NOT IN ({$this->Status_OUTSOURCING}, {$this->Status_RESIGN})
                    AND {$mLeaveBalance->table}.md_employee_id IN ({$arrEmpID})
                    AND {$mLeaveBalance->table}.year = {$year}"];

                    $arrEmp = $mEmployee->where([
                        'isactive'          => 'Y',
                    ])->whereNotIn('md_status_id', [$this->Status_OUTSOURCING, $this->Status_RESIGN])
                        ->whereIn('md_employee_id', $employee)
                        ->where($subQuery)
                        ->orderBy('value', 'ASC')
                        ->findAll();
                } else {
                    $where = ["md_employee.isactive = 'Y'
                    AND md_employee.md_status_id NOT IN ({$this->Status_OUTSOURCING}, {$this->Status_RESIGN})
                    AND {$mLeaveBalance->table}.year = {$year}"];

                    $arrEmp = $mEmployee->where([
                        'isactive'          => 'Y',
                    ])->whereNotIn('md_status_id', [$this->Status_OUTSOURCING, $this->Status_RESIGN])
                        ->where($subQuery)
                        ->orderBy('value', 'ASC')
                        ->findAll();
                }

                $this->processBalance($arrEmp, $year, $prevYear);

                $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);
                $number = $this->request->getPost('start');

                foreach ($list as $val) :
                    $row = [];

                    $row[] = $val->employee;
                    $row[] = $val->employee_fullname;
                    $row[] = $val->balance_amount;
                    $row[] = $val->year;
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

    public function processBalance($array, $year, $prevYear)
    {
        $mRule = new M_Rule($this->request);
        $mLeaveBalance = new M_LeaveBalance($this->request);
        $mMassLeave = new M_MassLeave($this->request);

        if (empty($array))
            return false;

        $rule = $mRule->where([
            'name'      => 'Saldo Cuti Tahunan',
            'isactive'  => 'Y'
        ])->first();

        $amount = 0;

        if ($rule)
            $amount = $rule->condition ?: $rule->value;

        $massLeave = $mMassLeave->where([
            'isactive'                      => 'Y',
            'isaffect'                      => 'Y',
            'date_format(startdate,"%Y")'   => $prevYear
        ])->findAll();

        $totalMass = count($massLeave);
        log_message("info", $prevYear);
        $amount = abs($amount);

        $data = [];
        foreach ($array as $row) {
            if ($row->getStatusId() == $this->Status_PERMANENT) {
                $data[] = [
                    "md_employee_id"    => $row->md_employee_id,
                    "submissiondate"    => date('Y-m-d'),
                    "annual_allocation" => $amount,
                    "balance_amount"    => $amount - $totalMass,
                    "year"              => $year,
                    "created_by"        => $this->session->get('sys_user_id'),
                    "updated_by"        => $this->session->get('sys_user_id')
                ];
            }
        }

        return $mLeaveBalance->builder->insertBatch($data);
    }
}

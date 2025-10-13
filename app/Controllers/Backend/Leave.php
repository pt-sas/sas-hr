<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use App\Models\M_AccessMenu;
use App\Models\M_Employee;
use App\Models\M_AbsentDetail;
use App\Models\M_Configuration;
use App\Models\M_Holiday;
use App\Models\M_EmpWorkDay;
use App\Models\M_Rule;
use App\Models\M_WorkDetail;
use App\Models\M_LeaveBalance;
use App\Models\M_MassLeave;
use App\Models\M_Transaction;
use App\Models\M_SubmissionCancelDetail;
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
        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelect();
            $join = $this->model->getJoin();
            $order = [
                '', // Hide column
                '', // Number column
                'trx_absent.documentno',
                'trx_absent.docstatus',
                'md_employee.fullname',
                'trx_absent.nik',
                'md_branch.name',
                'md_division.name',
                'trx_absent.submissiondate',
                'trx_absent.startdate',
                'trx_absent.receiveddate',
                'trx_absent.reason',
                'sys_user.name'
            ];
            $search = [
                'trx_absent.documentno',
                'trx_absent.docstatus',
                'md_employee.fullname',
                'trx_absent.nik',
                'md_branch.name',
                'md_division.name',
                'trx_absent.submissiondate',
                'trx_absent.startdate',
                'trx_absent.enddate',
                'trx_absent.receiveddate',
                'trx_absent.reason',
                'sys_user.name'
            ];
            $sort = ['trx_absent.submissiondate' => 'DESC'];

            // TODO : Get Employee List
            $where['md_employee.md_employee_id'] = ['value' => $this->access->getEmployeeData()];

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
                $row[] = docStatus($value->docstatus);
                $row[] = $value->employee_fullname;
                $row[] = $value->nik;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = format_dmy($value->startdate, '-') . " s/d " . format_dmy($value->enddate, '-');
                $row[] = !is_null($value->receiveddate) ? format_dmy($value->receiveddate, '-') : "";
                $row[] = $value->reason;
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
            $employeeId = $post['md_employee_id'];

            try {
                $this->entity->fill($post);

                if (!$this->validation->run($post, 'leave')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $holidays = $mHoliday->getHolidayDate();
                    $startDate = date("Y-m-d", strtotime($post['startdate']));
                    $endDate = date('Y-m-d', strtotime($post['enddate']));
                    $subDate = date('Y-m-d', strtotime($post['submissiondate']));
                    $nextYear = date('Y', strtotime('+1 year'));

                    $rule = $mRule->where([
                        'name'      => 'Cuti',
                        'isactive'  => 'Y'
                    ])->first();

                    $minDays = $rule && !empty($rule->min) ? $rule->min : 1;
                    $maxDays = $rule && !empty($rule->max) ? $rule->max : 1;

                    //TODO : Get work day employee
                    $workDay = $mEmpWork->where([
                        'md_employee_id'    => $post['md_employee_id'],
                        'validfrom <='      => $startDate,
                        'validto >='        => $endDate
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

                        // TODO : Get Minimum Dates for Submission Leave
                        $nextDate = lastWorkingDays($subDate, $holidays, $minDays, false, $daysOff);
                        $lastDate = end($nextDate);

                        //TODO : Get submission one day
                        $whereClause = "v_all_submission.md_employee_id = {$employeeId}";
                        $whereClause .= " AND DATE_FORMAT(v_all_submission.date, '%Y-%m-%d') BETWEEN '{$startDate}' AND '{$endDate}'";
                        $whereClause .= " AND v_all_submission.submissiontype IN (" . implode(", ", $this->Form_Satu_Hari) . ")";
                        $whereClause .= " AND v_all_submission.isagree IN ('{$this->LINESTATUS_Disetujui}', '{$this->LINESTATUS_Realisasi_HRD}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Approval}')";
                        $trx = $this->model->getAllSubmission($whereClause)->getRow();

                        //TODO : Get Max Days for Submission Future
                        $addDays = lastWorkingDays($subDate, [], $maxDays, false, [], true);
                        $addDays = end($addDays);

                        // TODO : Calculate Total Days Leave
                        $dateRange = getDatesFromRange($startDate, $endDate, $holidays, 'Y-m-d', 'all', $daysOff);

                        $amountThisYear = [];
                        $amountNextYear = [];

                        foreach ($dateRange as $date) {
                            if (date('Y', strtotime($date)) == $nextYear) {
                                $amountNextYear[] = $date;
                            } else {
                                $amountThisYear[] = $date;
                            }
                        }

                        // TODO : Get Leave Balance
                        $leaveBalance = $mLeaveBalance->getTotalBalance($employeeId, date("Y", strtotime($startDate)));
                        $leaveBalanceNextYear = !empty($amountNextYear) ? $mLeaveBalance->getNextYearBalance($employeeId) : null;

                        if ($trx) {
                            $response = message('success', false, 'Tidak bisa mengajukan pada rentang tanggal, karena sudah ada pengajuan lain');
                        } else if (empty($leaveBalance) && empty($leaveBalanceNextYear)) {
                            $response = message('success', false, 'Saldo cuti tidak tersedia');
                        } else if ($endDate > $addDays) {
                            $response = message('success', false, 'Tanggal selesai melewati tanggal ketentuan');
                        } else if ($startDate <= $lastDate) {
                            $response = message('success', false, 'Tidak bisa mengajukan pada tanggal ' . format_dmy($startDate, "-") . ', karena tidak sesuai dengan batas pengajuan');
                        } else {
                            // Cek apakah saldo carry over ada dan belum expired
                            $balance = 0;

                            if (!empty($leaveBalance)) {
                                $carryOverValid = ($leaveBalance->carry_over_expiry_date && $endDate <= date('Y-m-d', strtotime($leaveBalance->carry_over_expiry_date)));

                                $balance = $carryOverValid ? $leaveBalance->carried_over_amount + $leaveBalance->balance_amount : $leaveBalance->balance_amount;
                                $balance = $balance - $leaveBalance->reserved;
                            }

                            $balanceNextYear = !empty($leaveBalanceNextYear) ? $leaveBalanceNextYear->balance : 0;

                            $amountThisYear = count($amountThisYear);
                            $amountNextYear = count($amountNextYear);

                            if (!empty($amountNextYear) && $amountNextYear > $balanceNextYear) {
                                $response = message('success', false, 'Saldo tahun depan tidak cukup');
                            } else if (!empty($amountThisYear) && $amountThisYear > $balance) {
                                $response = message('success', false, 'Saldo cuti tidak cukup atau sudah expired');
                            } else {
                                $this->entity->fill($post);

                                if ($this->isNew()) {
                                    $this->entity->setDocStatus($this->DOCSTATUS_Drafted);
                                    $docNo = $this->model->getInvNumber("submissiontype", $this->model->Pengajuan_Cuti, $post, $this->session->get('sys_user_id'));
                                    $this->entity->setDocumentNo($docNo);
                                }
                                $response = $this->save();
                            }
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
                $list[0]->availableleavedays = intval($list[0]->availableleavedays);
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

            $endDate = date('Y-m-d', strtotime($row->enddate));
            $startDate = date("Y-m-d", strtotime($row->startdate));
            $nextYear = date('Y', strtotime('+1 year'));

            try {
                if (!empty($_DocAction)) {
                    if ($_DocAction === $row->getDocStatus()) {
                        $response = message('error', true, 'Silahkan refresh terlebih dahulu');
                    } else if ($_DocAction === $this->DOCSTATUS_Completed) {
                        $holidays = $mHoliday->getHolidayDate();

                        $workDay = $mEmpWork->where([
                            'md_employee_id'    => $row->md_employee_id,
                            'validfrom <='      => $startDate,
                            'validto >='        => $endDate
                        ])->orderBy('validfrom', 'ASC')->first();

                        //TODO : Get Work Detail
                        $whereClause = "md_work_detail.isactive = 'Y'";
                        $whereClause .= " AND md_employee_work.md_employee_id = $row->md_employee_id";
                        $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
                        $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getResult();

                        $daysOff = getDaysOff($workDetail);

                        $dateRange = getDatesFromRange($startDate, $endDate, $holidays, 'Y-m-d', 'all', $daysOff);

                        $amountThisYear = [];
                        $amountNextYear = [];

                        foreach ($dateRange as $date) {
                            if (date('Y', strtotime($date)) == $nextYear) {
                                $amountNextYear[] = $date;
                            } else {
                                $amountThisYear[] = $date;
                            }
                        }

                        //TODO : Get submission one day
                        $whereClause = "v_all_submission.md_employee_id = {$row->md_employee_id}";
                        $whereClause .= " AND DATE_FORMAT(v_all_submission.date, '%Y-%m-%d') BETWEEN '{$startDate}' AND '{$endDate}'";
                        $whereClause .= " AND v_all_submission.submissiontype IN (" . implode(", ", $this->Form_Satu_Hari) . ")";
                        $whereClause .= " AND v_all_submission.isagree IN ('{$this->LINESTATUS_Disetujui}', '{$this->LINESTATUS_Realisasi_HRD}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Approval}')";
                        $trx = $this->model->getAllSubmission($whereClause)->getRow();

                        $leaveBalance = $mLeaveBalance->getTotalBalance($row->md_employee_id, date("Y", strtotime($startDate)));
                        $leaveBalanceNextYear = !empty($amountNextYear) ? $mLeaveBalance->getNextYearBalance($row->md_employee_id) : null;

                        if ($trx) {
                            $response = message('success', false, 'Tidak bisa proses pengajuan, karena sudah ada pengajuan lain');
                        } else if (empty($leaveBalance) && empty($leaveBalanceNextYear)) {
                            $response = message('error', true, 'Saldo cuti tidak tersedia');
                        } else {
                            $balance = 0;
                            if (!empty($leaveBalance)) {
                                $carryOverValid = ($leaveBalance->carry_over_expiry_date && $endDate <= date('Y-m-d', strtotime($leaveBalance->carry_over_expiry_date)));

                                $balance = $carryOverValid ? $leaveBalance->carried_over_amount + $leaveBalance->balance_amount : $leaveBalance->balance_amount;
                                $balance = $balance - $leaveBalance->reserved;
                            }

                            $balanceNextYear = !empty($leaveBalanceNextYear) ? $leaveBalanceNextYear->balance : 0;

                            $amountThisYear = count($amountThisYear);
                            $amountNextYear = count($amountNextYear);

                            if (!empty($amountNextYear) && $amountNextYear > $balanceNextYear) {
                                $response = message('success', false, 'Saldo tahun depan tidak cukup');
                            } else if (!empty($amountThisYear) && $amountThisYear > $balance) {
                                $response = message('success', false, 'Saldo cuti tidak cukup atau sudah expired');
                            } else {
                                $line = $this->modelDetail->where($this->model->primaryKey, $_ID)->find();

                                if (empty($line)) {
                                    // TODO : Create Line if not exist
                                    $data = [
                                        'id'        => $_ID,
                                        'created_by' => $this->access->getSessionUser(),
                                        'updated_by' => $this->access->getSessionUser()
                                    ];

                                    $this->model->createAbsentDetail($data, $row);
                                }

                                $this->message = $cWfs->setScenario($this->entity, $this->model, $this->modelDetail, $_ID, $_DocAction, $menu, $this->session, null, true);
                                $response = message('success', true, true);
                            }
                        }
                    } else if ($_DocAction === $this->DOCSTATUS_Voided) {
                        $this->entity->setDocStatus($this->DOCSTATUS_Voided);
                        $response = $this->save();
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
                    if ($row->table === 'trx_submission_cancel_detail') {
                        $refModel = new M_SubmissionCancelDetail($this->request);
                    } else if ($row->table === 'trx_assignment') {
                        $refModel = new M_AssignmentDate($this->request);
                    } else {
                        $refModel = new M_AbsentDetail($this->request);
                    }
                    $lineRef = $refModel->getDetail($refModel->primaryKey, $row->ref_absent_detail_id)->getRow();
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
                    AND md_employee.md_status_id IN ({$this->Status_PERMANENT}, {$this->Status_PROBATION})
                    AND {$mLeaveBalance->table}.md_employee_id IN ({$arrEmpID})
                    AND {$mLeaveBalance->table}.year = {$year}"];

                    $arrEmp = $mEmployee->where([
                        'isactive'          => 'Y',
                    ])->whereIn('md_status_id', [$this->Status_PERMANENT, $this->Status_PROBATION])
                        ->whereIn('md_employee_id', $employee)
                        ->where($subQuery)
                        ->orderBy('value', 'ASC')
                        ->findAll();
                } else {
                    $where = ["md_employee.isactive = 'Y'
                    AND md_employee.md_status_id IN ({$this->Status_PERMANENT}, {$this->Status_PROBATION})
                    AND {$mLeaveBalance->table}.year = {$year}"];

                    $arrEmp = $mEmployee->where([
                        'isactive'          => 'Y',
                    ])->whereIn('md_status_id', [$this->Status_PERMANENT, $this->Status_PROBATION])
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
                    $row[] = intval($val->balance_amount);
                    $row[] = $val->startdate ? format_dmy($val->startdate, "-") : "";
                    $row[] = $val->enddate ? format_dmy($val->enddate, "-") : "";
                    $row[] = intval($val->carried_over_amount);
                    $row[] = $val->carry_over_expiry_date ? format_dmy($val->carry_over_expiry_date, "-") : "";
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
        $mConfig = new M_Configuration($this->request);
        $mRule = new M_Rule($this->request);
        $mLeaveBalance = new M_LeaveBalance($this->request);
        $mTransaction = new M_Transaction($this->request);
        $mMassLeave = new M_MassLeave($this->request);
        $mAbsent = new M_Absent($this->request);

        if (empty($array))
            return false;

        try {
            $rule = $mRule->where([
                'name'      => 'Saldo Cuti Tahunan',
                'isactive'  => 'Y'
            ])->first();

            $amount = 0;
            $dayCutOff = $mConfig->where([
                'isactive'  => 'Y',
                'name'      => 'DAY_CUT_OFF_LEAVE'
            ])->first();
            $dayCutOff = $dayCutOff->value;

            $carryOver = $mConfig->where([
                'isactive'  => 'Y',
                'name'      => 'CARRY_OVER_AMOUNT_LEAVE_BALANCE'
            ])->first();
            $isCarryOver = $carryOver->value;

            $carryExpBy = $mConfig->where([
                'isactive'  => 'Y',
                'name'      => 'CARRY_OVER_EXPIRED_BY'
            ])->first();
            $isCarryExpBy = $carryExpBy->value;

            if ($rule)
                $amount = $rule->condition ?: $rule->value;

            $amount = abs($amount);

            $totalMassLeave = $mMassLeave->where([
                'isactive'                      => 'Y',
                'isaffect'                      => 'Y',
                'date_format(startdate,"%Y")'   => $prevYear
            ])->orderBy('startdate', 'ASC')
                ->findAll();

            // $totalMassCount = count($totalMassLeave);

            $dataInsert = [];
            $dataUpdate = [];
            $dataLeaveUsage = [];

            foreach ($array as $row) {
                $carryBalance = 0;
                $startDate = null;
                $endDate = null;
                $carryExpDate = null;

                $registerDate = $row->registerdate;
                $startDateOfYear = date('Y-m-d', strtotime("first day of january {$year}"));

                //* Konversi tanggal ke timestamp 
                $startDateTimestamp = strtotime($registerDate);
                $endDateTimestamp = strtotime($startDateOfYear);

                //* Hari dalam bulan dari registerDate
                $dayOfMonth = date('j', $startDateTimestamp);

                //* Tanggal terakhir tahun dari startDateOfYear 
                $lastDayOfYear = date('Y-12-31', $endDateTimestamp);

                //* Tanggal terakhir tahun dari registerDate 
                $lastDayOfRegister = date('Y-12-31', $startDateTimestamp);

                //* Startdate <= 5 Januari 
                $yearOfStartDate = date('Y', $startDateTimestamp);
                $isBeforeOrEqualJanuary5 = $startDateTimestamp <= strtotime("January {$dayCutOff}, {$yearOfStartDate}");
                //* Tentukan tanggal 5 Januari tahun berikutnya 
                $nextYearCutOff = strtotime('+1 year', strtotime("January {$dayCutOff}, {$yearOfStartDate}"));

                $monthsDifference = monthsDifference($registerDate, $lastDayOfRegister);

                $registerMassLeave = $mMassLeave->where([
                    'isactive'                      => 'Y',
                    'isaffect'                      => 'Y',
                    'date_format(startdate,"%Y")'   => $prevYear,
                    'startdate >='                  => $registerDate
                ])->orderBy('startdate', 'ASC')
                    ->findAll();

                // $registerMassCount = count($registerMassLeave);

                //TODO: Periksa apakah sudah 1 tahun atau lebih 
                if ($endDateTimestamp >= $nextYearCutOff || $isBeforeOrEqualJanuary5) {
                    // TODO : Do Iteration Checking Submission on Mass Leave Date
                    $totalMassCount = 0;
                    $massLeaveCut = [];
                    foreach ($totalMassLeave as $leaveDate) {
                        $date = date('Y-m-d', strtotime($leaveDate->startdate));

                        $whereClause = "v_all_submission.md_employee_id = {$row->md_employee_id}";
                        $whereClause .= " AND v_all_submission.submissiontype IN ({$mAbsent->Pengajuan_Penugasan}, {$mAbsent->Pengajuan_Tugas_Kantor}, {$mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari})";
                        $whereClause .= " AND DATE(v_all_submission.date) = '{$date}'";
                        $whereClause .= " AND v_all_submission.isagree = '{$this->LINESTATUS_Disetujui}'";
                        $whereClause .= " AND v_all_submission.docstatus = '{$this->DOCSTATUS_Completed}'";
                        $trxMassLeave = $mAbsent->getAllSubmission($whereClause)->getRow();

                        if (!$trxMassLeave) {
                            $totalMassCount++;
                            $massLeaveCut[] = $leaveDate;
                        }
                    }

                    $balance = $amount - $totalMassCount;

                    $dataLeaveUsage[] = [
                        "transactiondate"   => $startDateOfYear,
                        "transactiontype"   => 'C+',
                        "year"              => $year,
                        "amount"            => $amount,
                        "md_employee_id"    => $row->md_employee_id,
                        "isprocessed"       => "Y",
                        "created_by"        => $this->session->get('sys_user_id'),
                        "updated_by"        => $this->session->get('sys_user_id')
                    ];

                    if (!empty($massLeaveCut))
                        foreach ($massLeaveCut as $item) {
                            $leaveUsage = -1;

                            $dataLeaveUsage[] = [
                                "transactiondate"   => $item->startdate,
                                "transactiontype"   => 'C-',
                                "year"              => $year,
                                "amount"            => $leaveUsage,
                                "md_employee_id"    => $row->md_employee_id,
                                "isprocessed"       => "Y",
                                "created_by"        => $this->session->get('sys_user_id'),
                                "updated_by"        => $this->session->get('sys_user_id'),
                                "description"       => $item->name
                            ];
                        }
                } else if ($dayOfMonth <= $dayCutOff) {
                    // TODO : Do Iteration Checking Submission on Mass Leave Date
                    $totalMassCount = 0;
                    $massLeaveCut = [];
                    foreach ($registerMassLeave as $leaveDate) {
                        $date = date('Y-m-d', strtotime($leaveDate->startdate));

                        $whereClause = "v_all_submission.md_employee_id = {$row->md_employee_id}";
                        $whereClause .= " AND v_all_submission.submissiontype IN ({$mAbsent->Pengajuan_Penugasan}, {$mAbsent->Pengajuan_Tugas_Kantor}, {$mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari})";
                        $whereClause .= " AND DATE(v_all_submission.date) = '{$date}'";
                        $whereClause .= " AND v_all_submission.isagree = '{$this->LINESTATUS_Disetujui}'";
                        $whereClause .= " AND v_all_submission.docstatus = '{$this->DOCSTATUS_Completed}'";
                        $trxMassLeave = $mAbsent->getAllSubmission($whereClause)->getRow();

                        if (!$trxMassLeave) {
                            $totalMassCount++;
                            $massLeaveCut[] = $leaveDate;
                        }
                    }
                    $balance = $monthsDifference - $totalMassCount;

                    $dataLeaveUsage[] = [
                        "transactiondate"   => $startDateOfYear,
                        "transactiontype"   => 'C+',
                        "year"              => $year,
                        "amount"            => $monthsDifference,
                        "md_employee_id"    => $row->md_employee_id,
                        "isprocessed"       => "Y",
                        "created_by"        => $this->session->get('sys_user_id'),
                        "updated_by"        => $this->session->get('sys_user_id')
                    ];

                    if (!empty($massLeaveCut))
                        foreach ($massLeaveCut as $item) {
                            $leaveUsage = -1;

                            $dataLeaveUsage[] = [
                                "transactiondate"   => $item->startdate,
                                "transactiontype"   => 'C-',
                                "year"              => $year,
                                "amount"            => $leaveUsage,
                                "md_employee_id"    => $row->md_employee_id,
                                "isprocessed"       => "Y",
                                "created_by"        => $this->session->get('sys_user_id'),
                                "updated_by"        => $this->session->get('sys_user_id'),
                                "description"       => $item->name
                            ];
                        }
                } else if ($dayOfMonth > $dayCutOff) {
                    // TODO : Do Iteration Checking Submission on Mass Leave Date
                    $totalMassCount = 0;
                    $massLeaveCut = [];
                    foreach ($registerMassLeave as $leaveDate) {
                        $date = date('Y-m-d', strtotime($leaveDate->startdate));

                        $whereClause = "v_all_submission.md_employee_id = {$row->md_employee_id}";
                        $whereClause .= " AND v_all_submission.submissiontype IN ({$mAbsent->Pengajuan_Penugasan}, {$mAbsent->Pengajuan_Tugas_Kantor}, {$mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari})";
                        $whereClause .= " AND DATE(v_all_submission.date) = '{$date}'";
                        $whereClause .= " AND v_all_submission.isagree = '{$this->LINESTATUS_Disetujui}'";
                        $whereClause .= " AND v_all_submission.docstatus = '{$this->DOCSTATUS_Completed}'";
                        $trxMassLeave = $mAbsent->getAllSubmission($whereClause)->getRow();

                        if (!$trxMassLeave) {
                            $totalMassCount++;
                            $massLeaveCut[] = $leaveDate;
                        }
                    }

                    $monthsDifference -= 1;
                    $balance = $monthsDifference - $totalMassCount;

                    $dataLeaveUsage[] = [
                        "transactiondate"   => $startDateOfYear,
                        "transactiontype"   => 'C+',
                        "year"              => $year,
                        "amount"            => $monthsDifference,
                        "md_employee_id"    => $row->md_employee_id,
                        "isprocessed"       => "Y",
                        "created_by"        => $this->session->get('sys_user_id'),
                        "updated_by"        => $this->session->get('sys_user_id')
                    ];

                    if (!empty($massLeaveCut))
                        foreach ($massLeaveCut as $item) {
                            $leaveUsage = -1;

                            $dataLeaveUsage[] = [
                                "transactiondate"   => $item->startdate,
                                "transactiontype"   => 'C-',
                                "year"              => $year,
                                "amount"            => $leaveUsage,
                                "md_employee_id"    => $row->md_employee_id,
                                "isprocessed"       => "Y",
                                "created_by"        => $this->session->get('sys_user_id'),
                                "updated_by"        => $this->session->get('sys_user_id'),
                                "description"       => $item->name
                            ];
                        }
                }

                if ($row->getStatusId() == $this->Status_PERMANENT) {
                    $startDate = $startDateOfYear;
                    $endDate = $lastDayOfYear;
                }

                //* Tanggal terakhir tahun dari PrevYear 
                $lastDayOfPrevYear = "{$prevYear}-12-31";

                if ($balance < 0) {
                    $annual = 0;

                    $dataInsert[] = [
                        "md_employee_id"    => $row->md_employee_id,
                        "submissiondate"    => $startDateOfYear,
                        "annual_allocation" => $annual,
                        "balance_amount"    => $balance,
                        "year"              => $prevYear,
                        "startdate"         => $lastDayOfPrevYear,
                        "enddate"           => $lastDayOfPrevYear,
                        "created_by"        => $this->session->get('sys_user_id'),
                        "updated_by"        => $this->session->get('sys_user_id')
                    ];

                    $dataLeaveUsage[] = [
                        "transactiondate"   => $startDateOfYear,
                        "transactiontype"   => 'C-',
                        "year"              => $prevYear,
                        "amount"            => $balance,
                        "md_employee_id"    => $row->md_employee_id,
                        "isprocessed"       => "Y",
                        "created_by"        => $this->session->get('sys_user_id'),
                        "updated_by"        => $this->session->get('sys_user_id')
                    ];

                    $balance = 0;
                }

                $prevBalance = $mLeaveBalance->where([
                    'year'              => $prevYear,
                    'md_employee_id'    => $row->md_employee_id
                ])->first();

                $prevLeaveBalance = $prevBalance->balance_amount ?? 0;

                if ($isCarryOver === "Y" && $prevLeaveBalance != 0) {
                    if ($isCarryExpBy === 'D') {
                        $carryExpBy = $mConfig->where([
                            'isactive'  => 'Y',
                            'name'      => 'CARRY_OVER_EXPIRED_BY_DAYS'
                        ])->first();

                        $carryExpDate = date('Y-m-d', strtotime("+ {$carryExpBy->value} days", $endDateTimestamp));
                    }

                    if ($isCarryExpBy === 'M') {
                        $carryExpBy = $mConfig->where([
                            'isactive'  => 'Y',
                            'name'      => 'CARRY_OVER_EXPIRED_BY_MONTH'
                        ])->first();

                        $carryExpDate = date('Y-m-d', strtotime("+ {$carryExpBy->value} month", $endDateTimestamp));
                    }

                    $carryBalance = $prevLeaveBalance;
                    $balanceAmt = 0;

                    $dataUpdate[] = [
                        "md_employee_id"        => $row->md_employee_id,
                        "year"                  => $prevYear,
                        "balance_amount"        => $balanceAmt,
                        "updated_by"            => $this->session->get('sys_user_id'),
                        "trx_leavebalance_id"   => $prevBalance->trx_leavebalance_id,
                    ];

                    $dataLeaveUsage[] = [
                        "transactiondate"   => $startDateOfYear,
                        "transactiontype"   => 'C-',
                        "year"              => $prevYear,
                        "amount"            => - ($carryBalance),
                        "md_employee_id"    => $row->md_employee_id,
                        "isprocessed"       => "N",
                        "created_by"        => $this->session->get('sys_user_id'),
                        "updated_by"        => $this->session->get('sys_user_id')
                    ];
                }

                $dataInsert[] = [
                    "md_employee_id"            => $row->md_employee_id,
                    "submissiondate"            => $startDateOfYear,
                    "annual_allocation"         => $amount,
                    "balance_amount"            => $balance,
                    "year"                      => $year,
                    "startdate"                 => $startDate,
                    "enddate"                   => $endDate,
                    "carried_over_amount"       => $carryBalance,
                    "carry_over_expiry_date"    => $carryExpDate,
                    "created_by"                => $this->session->get('sys_user_id'),
                    "updated_by"                => $this->session->get('sys_user_id')
                ];
            }

            if ($dataUpdate)
                $mLeaveBalance->builder->updateBatch($dataUpdate, $mLeaveBalance->primaryKey);

            if ($dataLeaveUsage) {
                foreach ($dataLeaveUsage as $data) {
                    $mTransaction->builder->insert($data);
                }
            }
            // $mTransaction->builder->insertBatch($dataLeaveUsage);

            return $mLeaveBalance->builder->insertBatch($dataInsert);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getAvailableDays()
    {
        $mLeaveBalance = new M_LeaveBalance($this->request);

        if ($this->request->isAJAX()) {
            $get = $this->request->getGet();

            $md_employee_id = $get['md_employee_id'];
            $startDate = $get['startdate'];

            $startOfYear = date('Y', strtotime($startDate));
            $nextYear = date('Y', strtotime('+1 year'));

            try {
                $balance = 0;
                $availableleave = 0;
                if ($startOfYear == $nextYear) {
                    $leaveBalance = $mLeaveBalance->getNextYearBalance($md_employee_id);

                    $balance = $leaveBalance->saldo_cuti;
                    $availableleave = $leaveBalance->balance;
                } else {
                    $leaveBalance = $mLeaveBalance->getTotalBalance($md_employee_id, $startOfYear);
                    $carryOverValid = ($leaveBalance->carry_over_expiry_date && $startDate <= date('Y-m-d', strtotime($leaveBalance->carry_over_expiry_date)));

                    $balance = $leaveBalance->balance_amount;
                    $availableleave = $carryOverValid ? $leaveBalance->carried_over_amount + $leaveBalance->balance_amount : $leaveBalance->balance_amount;
                    $availableleave = $availableleave - $leaveBalance->reserved;
                }

                $data = [
                    "balance" => intval($balance),
                    "availableleave" => intval($availableleave)
                ];

                $response = message('success', true, $data);
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
                if (isset($post['md_employee_id'])) {
                    if (isset($post['id']) && !empty($post['id'])) {
                        $id = $post['id'];
                        $subQuery = "(
                                    trx_absent.trx_absent_id = $id
                                    OR NOT EXISTS (
                                        SELECT 1 
                                        FROM trx_absent tab
                                        WHERE tab.reference_id = trx_absent.trx_absent_id
                                        AND tab.docstatus IN ('CO', 'DR', 'IP')
                                    )
                                )";
                    } else {
                        $subQuery = "NOT EXISTS (
                                SELECT 1 
                                FROM trx_absent tab
                                WHERE tab.reference_id = trx_absent.trx_absent_id
                                AND tab.docstatus IN ('CO', 'DR', 'IP')
                            )";
                    }

                    $subLine = "EXISTS (SELECT 1 FROM trx_absent_detail tad 
                                        WHERE trx_absent.trx_absent_id = tad.trx_absent_id
                                        AND tad.isagree = 'Y')";

                    $list = $this->model->where([
                        'md_employee_id'    => $post['md_employee_id'],
                        'docstatus'         => $this->DOCSTATUS_Completed,
                        'submissiontype'    => $this->model->Pengajuan_Cuti
                    ])->where($subQuery, null, true)->where($subLine, null, true)
                        ->orderBy('documentno', 'ASC')
                        ->findAll();
                } else {
                    $list = $this->model->where([
                        'docstatus'         => $this->DOCSTATUS_Completed,
                        'submissiontype'    => $this->model->Pengajuan_Cuti
                    ])->orderBy('documentno', 'ASC')
                        ->findAll();
                }

                foreach ($list as $key => $row) :
                    $response[$key]['id'] = $row->getAbsentId();
                    $response[$key]['text'] = $row->getDocumentNo();
                endforeach;
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}
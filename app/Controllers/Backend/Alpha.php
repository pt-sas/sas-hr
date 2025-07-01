<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use App\Models\M_Employee;
use App\Models\M_AccessMenu;
use App\Models\M_Attendance;
use App\Models\M_AbsentDetail;
use App\Models\M_EmpBranch;
use App\Models\M_EmpDivision;
use App\Models\M_AllowanceAtt;
use App\Models\M_Rule;
use App\Models\M_Holiday;
use App\Models\M_EmpWorkDay;
use App\Models\M_LeaveBalance;
use App\Models\M_RuleDetail;
use App\Models\M_WorkDetail;
use Config\Services;
use DateTime;
use Kint\Zval\Value;

class Alpha extends BaseController
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

        return $this->template->render('transaction/alpha/v_alpha', $data);
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

            $where['trx_absent.submissiontype'] = $this->model->Pengajuan_Alpa;

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
        $mAttendance = new M_Attendance($this->request);
        $mRule = new M_Rule($this->request);
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            $post["submissiontype"] = $this->model->Pengajuan_Alpa;
            $post["necessary"] = 'AL';
            $today = date('Y-m-d');
            $employeeId = $post['md_employee_id'];
            $day = date('w');

            try {
                if (!$this->validation->run($post, 'alpa')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $holidays = $mHoliday->getHolidayDate();
                    $startDate = $post['startdate'];
                    $endDate = $post['enddate'];
                    $nik = $post['nik'];
                    $submissionDate = $post['submissiondate'];

                    $rule = $mRule->where([
                        'name'      => 'Alpa',
                        'isactive'  => 'Y'
                    ])->first();

                    $countDays = $rule && !empty($rule->min) ? $rule->min : 1;

                    //TODO : Get attendance not present employee
                    $attNotPresent = $mAttendance->where([
                        'nik'       => $nik,
                        'date'      => $startDate,
                        'absent'    => 'N'


                    ])->first();

                    //TODO : Get next day attendance from enddate
                    $attPresentNextDay = $mAttendance->where([
                        'nik'       => $nik,
                        'date >'    => $endDate,
                        'absent'    => 'Y'
                    ])->orderBy('date', 'ASC')->first();

                    $nextDate = lastWorkingDays(
                        $attPresentNextDay->date,
                        $holidays,
                        $countDays,
                        false
                    );

                    //* index array 1 from variable attPresentNextDay first date
                    $lastDate = $nextDate[1];

                    //TODO : Get submission
                    $whereClause = "trx_absent.nik = $nik";
                    $whereClause .= " AND trx_absent.startdate >= '$startDate' AND trx_absent.enddate <= '$endDate'";
                    $whereClause .= " AND trx_absent.docstatus = '$this->DOCSTATUS_Completed'";
                    $whereClause .= " AND trx_absent_detail.isagree = 'Y'";
                    $trx = $this->modelDetail->getAbsentDetail($whereClause)->getResult();

                    $subDate = date('Y-m-d', strtotime($submissionDate));

                    $workDay = $mEmpWork->where([
                        'md_employee_id'    => $post['md_employee_id'],
                        'validfrom <='      => $today,
                        'validto >='        => $today
                    ])->orderBy('validfrom', 'ASC')->first();

                    $day = strtoupper(formatDay_idn($day));

                    //TODO : Get Work Detail
                    $whereClause = "md_work_detail.isactive = 'Y'";
                    $whereClause .= " AND md_employee_work.md_employee_id = $employeeId";
                    $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
                    $whereClause .= " AND md_day.name = '$day'";
                    $work = $mWorkDetail->getWorkDetail($whereClause)->getRow();

                    if (is_null($workDay)) {
                        $response = message('success', false, 'Hari kerja belum ditentukan');
                    } else if (is_null($work)) {
                        $response = message('success', false, 'Tidak terdaftar dalam hari kerja');
                    } else if (!is_null($attPresentNextDay) && !($lastDate >= $subDate) && $workDay && $work && $attNotPresent) {
                        $response = message('success', false, 'Maksimal tanggal pengajuan pada tanggal : ' . format_dmy($lastDate, '-'));
                    } else if ($trx) {
                        $response = message('success', false, 'Tidak bisa mengajukan pada rentang tanggal, karena sudah ada pengajuan lain');
                    } else {
                        $this->entity->fill($post);

                        if ($this->isNew()) {
                            $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                            $docNo = $this->model->getInvNumber("submissiontype", $post["submissiontype"], $post, $this->session->get('sys_user_id'));
                            $this->entity->setDocumentNo($docNo);
                        }

                        $response = $this->save();
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
                $list[0]->setStartDate(format_dmy($list[0]->startdate, "-"));
                $list[0]->setEndDate(format_dmy($list[0]->enddate, "-"));

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
                $result = $this->model->delete($id);
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
        if ($this->request->isAJAX()) {
            $post = $this->request->getVar();

            $_ID = $post['id'];
            $_DocAction = $post['docaction'];

            $row = $this->model->find($_ID);
            $menu = $this->request->uri->getSegment(2);

            try {
                if (!empty($_DocAction)) {
                    if ($_DocAction === $row->getDocStatus()) {
                        $response = message('error', true, 'Silahkan refresh terlebih dahulu');
                    } else if ($_DocAction === $this->DOCSTATUS_Completed) {
                        $this->message = $cWfs->setScenario($this->entity, $this->model, $this->modelDetail, $_ID, $_DocAction, $menu, $this->session);
                        $response = message('success', true, true);
                    } else if ($_DocAction === $this->DOCSTATUS_Unlock) {
                        $this->entity->setDocStatus($this->DOCSTATUS_Drafted);
                        $response = $this->save();
                    } else if ($_DocAction === $this->DOCSTATUS_Voided) {
                        $mRule = new M_Rule($this->request);
                        $mRuleDetail = new M_RuleDetail($this->request);
                        $mAllowance = new M_AllowanceAtt($this->request);
                        $mLeaveBalance = new M_LeaveBalance($this->request);

                        $detail = $this->modelDetail->where(['trx_absent_id' => $post['id'], 'isagree' => 'Y'])->find();

                        /**
                         * Update Pengajuan absent detail
                         */
                        foreach ($detail as $key => $value) {
                            $hold = 'H';
                            $cancel = 'C';

                            $this->model = new M_AbsentDetail($this->request);
                            $this->entity = new \App\Entities\AbsentDetail();
                            $this->entity->isagree = $cancel;
                            $this->entity->trx_absent_detail_id = $value->trx_absent_detail_id;
                            $this->save();

                            /**
                             * Update Pengajuan ref absent detail
                             */
                            if ($value->ref_absent_detail_id != null) {
                                $refDetail = $this->modelDetail->where('trx_absent_detail_id', $value->ref_absent_detail_id)->first();
                                $whereClause = "trx_absent.trx_absent_id = " . $refDetail->trx_absent_id;
                                $lineNo = $this->modelDetail->getLineNo($whereClause);

                                /**
                                 * Update Old Absent Detail
                                 */
                                // $this->model = new M_AbsentDetail($this->request);
                                // $this->entity = new \App\Entities\AbsentDetail();
                                // $this->entity->isagree = $cancel;
                                // $this->entity->trx_absent_detail_id = $refDetail->trx_absent_detail_id;
                                // $this->save();

                                /**
                                 * Inserting New Absent Detail
                                 */
                                $this->model = new M_AbsentDetail($this->request);
                                $this->entity = new \App\Entities\AbsentDetail();
                                $this->entity->trx_absent_id = $refDetail->trx_absent_id;
                                $this->entity->isagree = $hold;
                                $this->entity->lineno = $lineNo;
                                $this->entity->date = $refDetail->date;
                                $this->save();

                                /**
                                 * Update DocStatus in Reference Document
                                 */
                                $this->model = new M_Absent($this->request);
                                $this->entity = new \App\Entities\Absent();
                                $this->entity->setDocStatus($this->DOCSTATUS_Inprogress);
                                $this->entity->setAbsentId($refDetail->trx_absent_id);
                                $this->save();
                            }
                        }

                        $this->model = new M_Absent($this->request);
                        $this->entity = new \App\Entities\Absent();
                        $this->entity->setDocStatus($this->DOCSTATUS_Voided);
                        $this->save();

                        // This Section for returning amount of sanksi
                        $rule = $mRule->where([
                            'name'      => 'Alpa',
                            'isactive'  => 'Y'
                        ])->first();

                        if ($rule) {
                            $ruleDetail = $mRuleDetail->where('md_rule_id = ' . $rule->md_rule_id)->findAll();

                            if ($rule->condition === "")
                                $amount = $rule->value;

                            // Returning Amount Allowance
                            if ($amount != 0 && $detail) {
                                foreach ($detail as $item) {
                                    $arr[] = [
                                        "record_id"         => $_ID,
                                        "table"             => $this->model->table,
                                        "submissiontype"    => $row->getSubmissionType(),
                                        "submissiondate"    => $item->date,
                                        "md_employee_id"    => $row->getEmployeeId(),
                                        "amount"            => $amount,
                                        "created_by"        => $this->access->getSessionUser(),
                                        "updated_by"        => $this->access->getSessionUser()
                                    ];
                                }
                            }

                            //  Return Leave or Allowance
                            foreach ($ruleDetail as $value) {
                                $balance = $mLeaveBalance->getBalance('md_employee_id', $row->getEmployeeId());

                                if (!empty($balance)) {
                                    if ($value->name === "Sanksi Alpa Cuti") {
                                        $entity = new \App\Entities\LeaveBalance();

                                        foreach ($detail as $item) {
                                            $entity->record_id = $_ID;
                                            $entity->table = $this->model->table;
                                            $entity->md_employee_id = $row->getEmployeeId();
                                            $entity->submissiondate = $item->date;
                                            $entity->amount = abs($value->value);

                                            $mLeaveBalance->save($entity);
                                        }
                                    }
                                } else {
                                    if ($value->name === "Sanksi Alpa No Cuti") {
                                        $amount = $value->value;

                                        foreach ($detail as $item) {
                                            $arr[] = [
                                                "record_id"         => $_ID,
                                                "table"             => $this->model->table,
                                                "submissiontype"    => $row->getSubmissionType(),
                                                "submissiondate"    => $item->date,
                                                "md_employee_id"    => $row->getEmployeeId(),
                                                "amount"            => $amount,
                                                "created_by"        => $this->access->getSessionUser(),
                                                "updated_by"        => $this->access->getSessionUser()
                                            ];
                                        }
                                    }
                                }
                            }

                            $mAllowance->builder->insertBatch($arr);
                        }

                        $response = message('success', true, true);
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

    public function generateAlpa()
    {
        $mAttendance = new M_Attendance($this->request);
        $mEmpBranch = new M_EmpBranch($this->request);
        $mEmpDivision = new M_EmpDivision($this->request);
        $mHoliday = new M_Holiday($this->request);
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);
        $mHoliday = new M_Holiday($this->request);

        try {
            $post = $this->request->getVar();
            $attendance = json_decode($post['employee']);

            $today = date('Y-m-d');
            $todayTime = date('Y-m-d H:i:s');
            $agree = 'Y';

            $holidays = $mHoliday->getHolidayDate();
            $doc = [];

            //TODO: Adding for each data date
            $holidayClause = [];

            foreach ($holidays as $value) {
                $date = date('Y-m-d H:i:s', strtotime($value));
                $holidayClause[] = "'{$date}'";
            }

            //TODO: Grouping By Nik 
            foreach ($attendance as $item) {
                $item->date = date('Y-m-d', strtotime($item->date));
                $header[$item->nik][] = $item;
            }

            //TODO: Creating Alpa Header
            foreach ($header as $value) {
                $_id = $value[0]->id;
                $_nik = $value[0]->nik;

                $branch = $mEmpBranch->where('md_employee_id', $_id)->first();
                $division = $mEmpDivision->where('md_employee_id', $_id)->first();

                /**
                 *  This Section for getting employee days off
                 */
                $workDay = $mEmpWork->where([
                    'md_employee_id'    => $_id,
                    'validfrom <='      => $today,
                    'validto >='        => $today
                ])->orderBy('validfrom', 'ASC')->first();

                $whereClause = "md_work_detail.isactive = 'Y'";
                $whereClause .= " AND md_employee_work.md_employee_id = {$_id}";
                $whereClause .= " AND md_work.md_work_id = {$workDay->md_work_id}";
                $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getResult();

                $daysOff = getDaysOff($workDetail);

                //* Getting List Date
                $date = [];
                $number = 0;

                /** 
                 * This for grouping date when creating document alpha
                 */
                foreach ($value as $item) {
                    $whereClause = "v_attendance.nik = {$item->nik}";
                    $whereClause .= " AND v_attendance.date NOT IN " . "(" . implode(", ", $holidayClause) . ")";
                    $whereClause .= " AND DATE_FORMAT(v_attendance.date," . "'%w'" . ") NOT IN " . "(" . implode(", ", $daysOff) . ")";

                    $whereDate = " AND v_attendance.date > '$item->date'";
                    $nextClause = $whereClause;
                    $nextClause .= $whereDate;
                    $nextDay = $mAttendance->getAttendance($nextClause)->getRow();

                    $whereDate = " AND v_attendance.date < '$item->date'";
                    $beforeClause = $whereClause;
                    $beforeClause .= $whereDate;
                    $beforeDay = $mAttendance->getAttendance($beforeClause)->getRow();

                    if (
                        is_null($nextDay) && // Next attendance is null
                        $beforeDay && // Before attendance is exists
                        count($date) > 0 && // array date is exists data
                        count($date[$number]) > 0 && // array date[$number] is exists data
                        count(getDatesFromRange(end($date[$number]), $item->date, $holidays, "Y-m-d", "all", $daysOff)) > 2 // Check range date
                    ) {
                        $number++;
                    }

                    $date[$number][] = $item->date;
                }

                $post['necessary'] = 'AL';
                $post['submissiondate'] = $today;

                foreach ($date as $item) {
                    $this->model = new M_Absent($this->request);
                    $this->entity = new \App\Entities\Absent();

                    $startDate = min($item);
                    $endDate = max($item);

                    $this->entity->setNecessary($post['necessary']);
                    $this->entity->setSubmissionType($this->model->Pengajuan_Alpa);
                    $this->entity->setEmployeeId($_id);
                    $this->entity->setNik($_nik);
                    $this->entity->setBranchId($branch->md_branch_id);
                    $this->entity->setDivisionId($division->md_division_id);
                    $this->entity->setReceivedDate($todayTime);
                    $this->entity->setSubmissionDate($today);
                    $this->entity->setStartDate($startDate);
                    $this->entity->setEndDate($endDate);
                    $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                    $docNo = $this->model->getInvNumber("submissiontype", $this->model->Pengajuan_Alpa, $post, $this->session->get('sys_user_id'));
                    $this->entity->setDocumentNo($docNo);

                    $this->save();

                    // * Foreignkey id 
                    $_refID = $this->insertID;
                    $lineNo = 1;

                    //TODO: Creating Absent Line 
                    foreach ($item as $line) {
                        $mAbsentDetail = new M_AbsentDetail($this->request);
                        $detailEntity = new \App\Entities\AbsentDetail();

                        $detailEntity->isagree = $agree;
                        $detailEntity->trx_absent_id = $_refID;
                        $detailEntity->lineno = $lineNo;
                        $detailEntity->date = $line;
                        $detailEntity->created_by = $this->access->getSessionUser();
                        $detailEntity->updated_by = $this->access->getSessionUser();

                        $mAbsentDetail->save($detailEntity);

                        $lineNo++;
                    }

                    $doc[] = $docNo;

                    $this->model = new M_Absent($this->request);
                    $this->entity = new \App\Entities\Absent();

                    $this->entity->setDocStatus($this->DOCSTATUS_Completed);
                    $this->entity->setAbsentId($_refID);
                    $this->save();
                }
            }

            $response = message('success', true, 'Alpa telah digenerate dengan nomor ' . implode(", ", $doc));
        } catch (\Exception $e) {
            $response = message('error', false, $e->getMessage());
        }

        return $this->response->setJSON($response);
    }
}
<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_AbsentDetail;
use Config\Services;
use App\Models\M_Employee;
use App\Models\M_AccessMenu;
use App\Models\M_Absent;
use App\Models\M_Holiday;
use App\Models\M_Rule;
use App\Models\M_WorkDetail;
use App\Models\M_EmpWorkDay;
use App\Models\M_Division;
use App\Models\M_SubmissionCancelDetail;
use App\Models\M_RuleDetail;
use TCPDF;

class OfficeDuties extends BaseController
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

        return $this->template->render('transaction/officeduties/v_office_duties', $data);
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
                'md_branch.name',
                'md_division.name',
                'trx_absent.submissiondate',
                'trx_absent.startdate',
                'trx_absent.approveddate',
                'trx_absent.reason',
                'trx_absent.docstatus',
                'sys_user.name'
            ];
            $search = [
                'trx_absent.documentno',
                'md_employee.fullname',
                'md_branch.name',
                'md_division.name',
                'trx_absent.submissiondate',
                'trx_absent.startdate',
                'trx_absent.enddate',
                'trx_absent.approveddate',
                'trx_absent.reason',
                'trx_absent.docstatus',
                'sys_user.name'
            ];
            $sort = ['trx_absent.submissiondate' => 'DESC'];

            /**
             * Hak akses
             */
            $roleEmp = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_All_Data');
            $empDelegation = $mEmployee->getEmpDelegation($this->session->get('sys_user_id'));
            $arrAccess = $mAccess->getAccess($this->session->get("sys_user_id"));
            $arrEmployee = $mEmployee->getChartEmployee($this->session->get('md_employee_id'));

            if (!empty($empDelegation)) {
                $arrEmployee = array_unique(array_merge($arrEmployee, $empDelegation));
            }

            if ($arrAccess && isset($arrAccess["branch"]) && isset($arrAccess["division"])) {
                $arrBranch = $arrAccess["branch"];
                $arrDiv = $arrAccess["division"];

                $arrEmpBased = $mEmployee->getEmployeeBased($arrBranch, $arrDiv);

                if (!empty($empDelegation)) {
                    $arrEmpBased = array_unique(array_merge($arrEmpBased, $empDelegation));
                }

                if ($roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $arrMerge = array_unique(array_merge($arrEmpBased, $arrEmployee));

                    $where['md_employee.md_employee_id'] = [
                        'value'     => $arrMerge
                    ];
                } else if (!$roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $where['md_employee.md_employee_id'] = [
                        'value'     => $arrEmployee
                    ];
                } else if ($roleEmp && empty($this->session->get('md_employee_id'))) {
                    $where['md_employee.md_employee_id'] = [
                        'value'     => $arrEmpBased
                    ];
                } else {
                    $where['md_employee.md_employee_id'] = $this->session->get('md_employee_id');
                }
            } else if (!empty($this->session->get('md_employee_id'))) {
                $where['md_employee.md_employee_id'] = [
                    'value'     => $arrEmployee
                ];
            } else {
                $where['md_employee.md_employee_id'] = $this->session->get('md_employee_id');
            }

            $where['trx_absent.submissiontype'] = $this->model->Pengajuan_Tugas_Kantor;

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
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = format_dmy($value->startdate, '-') . " s/d " . format_dmy($value->enddate, '-');
                $row[] = !is_null($value->approveddate) ? format_dmy($value->approveddate, '-') : "";
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
        $mEmployee = new M_Employee($this->request);
        $mRule = new M_Rule($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            $table = json_decode($post['table']);
            //! Mandatory property for detail validation
            $post['line'] = countLine($table);
            $post['detail'] = [
                'table' => arrTableLine($table)
            ];

            try {
                if (!$this->validation->run($post, 'tugasKantor')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $post["submissiontype"] = $this->model->Pengajuan_Tugas_Kantor;
                    $post["necessary"] = 'TK';
                    $startDate = $post['startdate'];
                    $endDate = $post['enddate'];
                    $submissionDate = $post['submissiondate'];
                    $today = date('H:i');
                    $dateToday = date('Y-m-d');
                    $dateReq = date('Y-m-d', strtotime($startDate));
                    $subDate = date('Y-m-d', strtotime($submissionDate));
                    $day = date('w', strtotime($post['startdate']));
                    $holidays = $mHoliday->getHolidayDate();

                    $rule = $mRule->where([
                        'name'      => 'Tugas Kantor 1 Hari',
                        'isactive'  => 'Y'
                    ])->first();

                    $minDays = $rule && !empty($rule->min) ? $rule->min : 1;
                    $maxDays = $rule && !empty($rule->max) ? $rule->max : 1;

                    //TODO : Get work day employee
                    $workDay = $mEmpWork->where([
                        'md_employee_id'                           => $post['md_employee_id'],
                        'date_format(validto, "%Y-%m-%d") >='      => $dateToday
                    ])->orderBy('validfrom', 'ASC')->first();

                    if (is_null($workDay)) {
                        $response = message('success', false, 'Hari kerja belum ditentukan');
                    } else {
                        //TODO : Get Work Detail
                        $whereClause = "md_work_detail.isactive = 'Y'";
                        $whereClause .= " AND md_employee_work.md_employee_id = {$post['md_employee_id']}";
                        $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
                        $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getResult();

                        $daysOff = getDaysOff($workDetail);

                        //* last index of array from variable nextDate
                        $nextDate = lastWorkingDays($startDate, $holidays, $minDays, false, $daysOff);
                        $lastDate = end($nextDate);

                        //* last index of array from variable addDays
                        $addDays = lastWorkingDays($submissionDate, [], $maxDays, false, [], true);
                        $addDays = end($addDays);

                        //* For Validation Same Day but Checking Max Time
                        $ruleDetail = $rule ? $mRuleDetail->where(['md_rule_id' => $rule->md_rule_id, 'isactive' => 'Y'])->first() : null;
                        $todayMinutes = convertToMinutes($today);
                        $maxMinutes = $ruleDetail ? convertToMinutes(date("H:i", strtotime($ruleDetail->condition))) : null;

                        //TODO : Get submission
                        $date_range = getDatesFromRange($startDate, $endDate, $holidays, 'Y-m-d H:i:s', 'all', $daysOff);

                        foreach ($date_range as $date) {
                            $whereClause = "trx_absent.md_employee_id = {$post['md_employee_id']}";
                            $whereClause .= " AND trx_absent_detail.date = '$date'";
                            $whereClause .= " AND trx_absent.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')";
                            $whereClause .= " AND trx_absent_detail.isagree IN ('H','M','S','Y')";
                            $trx = $this->modelDetail->getAbsentDetail($whereClause)->getRow();

                            if (!empty($trx)) {
                                break;
                            }
                        }

                        if ($endDate > $addDays) {
                            $response = message('success', false, 'Tanggal selesai melewati tanggal ketentuan');
                        } else if ($lastDate < $subDate) {
                            $response = message('success', false, 'Tidak bisa mengajukan pada rentang tanggal, karena sudah selesai melewati tanggal ketentuan');
                        } else if ($dateReq === $subDate && ($maxMinutes && ($todayMinutes > $maxMinutes))) {
                            $response = message('success', false, 'Maksimal jam pengajuan ' . $ruleDetail->condition);
                        } else if ($trx) {
                            $date = format_dmy($trx->date, '-');
                            $response = message('success', false, "Tidak bisa mengajukan pada tanggal : {$date}, karena sudah ada pengajuan lain dengan no : {$trx->documentno}");
                        } else {
                            $this->entity->fill($post);

                            if ($this->isNew()) {
                                $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                                $docNo = $this->model->getInvNumber("submissiontype", $this->model->Pengajuan_Tugas_Kantor, $post, $this->session->get('sys_user_id'));
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

                //* Need to set data into date field in form
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


    //     public function getAssignmentDate()
    //     {
    //         if ($this->request->isAJAX()) {
    //             $mAbsentDetail = new M_AbsentDetail($this->request);
    //             $post = $this->request->getVar();
    //             $result = [];

    //             try {
    //                 $line = $this->modelSubDetail->where('trx_assignment_detail_id', $post['id'])->orderBy('date', 'ASC')->findAll();

    //                 foreach ($line as $row) {
    //                     $docNoRef = "";

    //                     if (!empty($row->reference_id)) {
    //                         $lineRef = $mAbsentDetail->getDetail('trx_absent_detail_id', $row->reference_id)->getRow();
    //                         $docNoRef = $lineRef->documentno;
    //                     }

    //                     $result[] = [
    //                         'date' => format_dmy($row->date, '-'),
    //                         'description' => $row->description ?? '',
    //                         'isagree' => statusRealize($row->isagree),
    //                         'reference_id' => $docNoRef
    //                     ];
    //                 }

    //                 $response = message('success', true, $result);
    //             } catch (\Exception $e) {
    //                 $response = message('error', false, $e->getMessage());
    //             }

    //             return $this->response->setJSON($response);
    //         }
    //     }
}
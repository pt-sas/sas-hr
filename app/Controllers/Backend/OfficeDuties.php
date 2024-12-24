<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_AbsentDetail;
use Config\Services;
use App\Models\M_Employee;
use App\Models\M_AccessMenu;
use App\Models\M_Assignment;
use App\Models\M_AssignmentDate;
use App\Models\M_AssignmentDetail;
use App\Models\M_Holiday;
use App\Models\M_Rule;
use App\Models\M_WorkDetail;
use App\Models\M_EmpWorkDay;
use App\Models\M_Division;
use App\Models\M_RuleDetail;
use TCPDF;

class OfficeDuties extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Assignment($this->request);
        $this->modelDetail = new M_AssignmentDetail($this->request);
        $this->modelSubDetail = new M_AssignmentDate($this->request);
        $this->entity = new \App\Entities\Assignment();
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
                'trx_assignment.documentno',
                'md_employee.fullname',
                'md_branch.name',
                'md_division.name',
                'trx_assignment.submissiondate',
                'trx_assignment.startdate',
                'trx_assignment.approveddate',
                'trx_assignment.reason',
                'trx_assignment.docstatus',
                'sys_user.name'
            ];
            $search = [
                'trx_assignment.documentno',
                'md_employee.fullname',
                'md_branch.name',
                'md_division.name',
                'trx_assignment.submissiondate',
                'trx_assignment.startdate',
                'trx_assignment.enddate',
                'trx_assignment.approveddate',
                'trx_assignment.reason',
                'trx_assignment.docstatus',
                'sys_user.name'
            ];
            $sort = ['trx_assignment.submissiondate' => 'DESC'];

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

                    $where['trx_assignment.md_employee_id'] = [
                        'value'     => $arrMerge
                    ];
                } else if (!$roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $where['trx_assignment.md_employee_id'] = [
                        'value'     => $arrEmployee
                    ];
                } else if ($roleEmp && empty($this->session->get('md_employee_id'))) {
                    $where['trx_assignment.md_employee_id'] = [
                        'value'     => $arrEmpBased
                    ];
                } else {
                    $where['trx_assignment.md_employee_id'] = $this->session->get('md_employee_id');
                }
            } else if (!empty($this->session->get('md_employee_id'))) {
                $where['trx_assignment.md_employee_id'] = [
                    'value'     => $arrEmployee
                ];
            } else {
                $where['trx_assignment.md_employee_id'] = $this->session->get('md_employee_id');
            }

            $where['trx_assignment.submissiontype'] = $this->model->Pengajuan_Tugas_Kantor;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_assignment_id;

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

                        $arrEmpId = array_map(function ($value) {
                            return $value->md_employee_id;
                        }, $table);

                        $empWork = $mEmployee
                            ->whereIn("md_employee_id", $arrEmpId)
                            ->where("NOT EXISTS (SELECT 1 
                                                FROM md_employee_work mew
						                        WHERE mew.md_employee_id = {$mEmployee->table}.md_employee_id
                                                AND date_format(validto, '%Y-%m-%d') >= '{$startDate}'
                                                AND (SELECT mwd.md_day_id
                                                    FROM md_work_detail mwd
                                                    WHERE mwd.md_work_id = mew.md_work_id
                                                    AND mwd.md_day_id = {$day}))")
                            ->findAll();

                        if ($endDate > $addDays) {
                            $response = message('success', false, 'Tanggal selesai melewati tanggal ketentuan');
                        } else if ($lastDate < $subDate) {
                            $response = message('success', false, 'Tidak bisa mengajukan pada rentang tanggal, karena sudah selesai melewati tanggal ketentuan');
                        } else if ($dateReq === $subDate && ($maxMinutes && ($todayMinutes > $maxMinutes))) {
                            $response = message('success', false, 'Maksimal jam pengajuan ' . $ruleDetail->condition);
                        } else if ($empWork) {
                            $value = implode(", ", array_map(function ($row) {
                                return $row->value;
                            }, $empWork));

                            $response = message('success', false, "Karyawan tidak terdaftar dalam hari kerja : [{$value}]");
                        } else {
                            $this->entity->fill($post);

                            if ($this->isNew()) {
                                $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                                $docNo = $this->model->getInvNumber("submissiontype", $this->model->Pengajuan_Tugas_Kantor, $post);
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
                        $this->message = $cWfs->setScenario($this->entity, $this->model, $this->modelDetail, $_ID, $_DocAction, $menu, $this->session);
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
        $employee = new M_Employee($this->request);
        $mAssignmentDate = new M_AssignmentDate($this->request);

        $post = $this->request->getPost();

        $table = [];

        $btnChildRow = new \App\Entities\Table();
        $btnChildRow->setClass("details-control");

        $fieldEmployee = new \App\Entities\Table();
        $fieldEmployee->setName("md_employee_id");
        $fieldEmployee->setIsRequired(true);
        $fieldEmployee->setType("select");
        $fieldEmployee->setClass("select2");
        $fieldEmployee->setField([
            'id'    => 'md_employee_id',
            'text'  => 'value'
        ]);
        $fieldEmployee->setLength(200);

        $fieldDesctiprion = new \App\Entities\Table();
        $fieldDesctiprion->setName("description");
        $fieldDesctiprion->setId("description");
        $fieldDesctiprion->setType("text");
        $fieldDesctiprion->setLength(250);

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName($this->modelDetail->primaryKey);
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");

        // ? Create
        if (empty($set)) {
            if (!$this->validation->run($post, 'TugasKantorAddRow')) {
                $table = $this->field->errorValidation($this->model->table, $post);
            } else {
                // if ($post['md_branch_id'] !== null || $post['md_division_id'] !== null) {
                //     $whereClause = "md_employee.isactive = 'Y'";

                //     if ($emp->getLevellingId() == 100002) {
                //         $whereClause .= " AND (md_employee.superior_id = $empId OR md_employee.md_employee_id = $empId)";
                //     } else {
                // $whereClause .= " AND md_employee_branch.md_branch_id = {$post['md_branch_id']}
                //         AND md_employee_division.md_division_id = {$post['md_division_id']}
                //         AND (md_employee.md_levelling_id IN (SELECT l.md_levelling_id
                //         FROM md_levelling l
                //         WHERE l.md_levelling_id > {$emp->md_levelling_id}))
                //         AND md_employee.md_status_id NOT IN ({$this->Status_RESIGN}, {$this->Status_OUTSOURCING})";
                // }

                // }


                $emp = $employee->find($this->session->get('md_employee_id'));
                $empId = $emp->getEmployeeId();

                $whereClause = "md_employee.isactive = 'Y'";

                if ($emp->getLevellingId() == 100002) {
                    $whereClause .= " AND (md_employee.superior_id = $empId OR md_employee.md_employee_id = $empId)";
                } else {
                    $whereClause .= " AND superior_id in (select e.md_employee_id from md_employee e where e.superior_id in (select e.md_employee_id from md_employee e where e.superior_id = $empId))";
                    $whereClause .= " OR md_employee.superior_id IN (SELECT e.md_employee_id FROM md_employee e WHERE e.superior_id = $empId)";
                    $whereClause .= " OR md_employee.superior_id = $empId";
                    $whereClause .= " AND md_employee.md_status_id NOT IN ({$this->Status_RESIGN}, {$this->Status_OUTSOURCING})";
                }

                $dataEmployee = $employee->getEmployee($whereClause);
                $fieldEmployee->setList($dataEmployee);

                $table = [
                    '',
                    $this->field->fieldTable($fieldEmployee),
                    $this->field->fieldTable($fieldDesctiprion),
                    $this->field->fieldTable($btnDelete)
                ];
            }
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $id = $row->getAssignmentId();
                $header = $this->model->where('trx_assignment_id', $id)->first();
                $subDetail = $mAssignmentDate->where('trx_assignment_detail_id', $row->getAssignmentDetailId())->first();

                // $whereClause = "md_employee.isactive = 'Y'";

                // if ($emp->getLevellingId() == 100002) {
                //     $whereClause .= " AND (md_employee.superior_id = $empId OR md_employee.md_employee_id = $empId)";
                // } else {
                //     $whereClause .= " AND md_employee_branch.md_branch_id = {$header->md_branch_id}
                //                 AND md_employee_division.md_division_id = {$header->md_division_id}
                //                 AND (md_employee.md_levelling_id IN (SELECT l.md_levelling_id
                //                 FROM md_levelling l
                //                 WHERE l.md_levelling_id > {$emp->md_levelling_id}))
                //                 AND md_employee.md_status_id NOT IN ({$this->Status_RESIGN}, {$this->Status_OUTSOURCING})";
                // }

                $emp = $employee->find($header->md_employee_id);
                $empId = $emp->getEmployeeId();

                $whereClause = "md_employee.isactive = 'Y'";

                if (
                    $emp->getLevellingId() == 100002
                ) {
                    $whereClause .= " AND (md_employee.superior_id = $empId OR md_employee.md_employee_id = $empId)";
                } else {
                    $whereClause .= " AND superior_id in (select e.md_employee_id from md_employee e where e.superior_id in (select e.md_employee_id from md_employee e where e.superior_id = $empId))";
                    $whereClause .= " OR md_employee.superior_id IN (SELECT e.md_employee_id FROM md_employee e WHERE e.superior_id = $empId)";
                    $whereClause .= " OR md_employee.superior_id = $empId";
                    $whereClause .= " AND md_employee.md_status_id NOT IN ({$this->Status_RESIGN}, {$this->Status_OUTSOURCING})";
                }

                $dataEmployee = $employee->getEmployee($whereClause);
                $fieldEmployee->setList($dataEmployee);

                $fieldEmployee->setValue($row->getEmployeeId());
                $fieldEmployee->setAttribute(['data-line-id' => $row->getAssignmentDetailId(), 'data-subdetail' => $subDetail ? 'Y' : 'N']);
                $fieldDesctiprion->setValue($row->getDescription());
                $btnDelete->setValue($row->getAssignmentDetailId());

                $table[] = [
                    '',
                    $this->field->fieldTable($fieldEmployee),
                    $this->field->fieldTable($fieldDesctiprion),
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        return json_encode($table);
    }


    public function getAssignmentDate()
    {
        if ($this->request->isAJAX()) {
            $mAbsentDetail = new M_AbsentDetail($this->request);
            $post = $this->request->getVar();
            $result = [];

            try {
                $line = $this->modelSubDetail->where('trx_assignment_detail_id', $post['id'])->orderBy('date', 'ASC')->findAll();

                foreach ($line as $row) {
                    $docNoRef = "";

                    if (!empty($row->reference_id)) {
                        $lineRef = $mAbsentDetail->getDetail('trx_absent_detail_id', $row->reference_id)->getRow();
                        $docNoRef = $lineRef->documentno;
                    }

                    $result[] = [
                        'date' => format_dmy($row->date, '-'),
                        'description' => $row->description ?? '',
                        'isagree' => statusRealize($row->isagree),
                        'reference_id' => $docNoRef
                    ];
                }

                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}
<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Bundling;
use App\Models\M_BundlingEvent;
use App\Models\M_BundlingParticipant;
use App\Models\M_Employee;
use App\Models\M_Overtime;
use App\Models\M_Rule;
use App\Models\M_RuleDetail;
use App\Models\M_Year;
use Config\Services;

class Bundling extends BaseController
{
    protected $baseSubType;

    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Bundling($this->request);
        $this->modelDetail = new M_BundlingParticipant($this->request);
        $this->modelSubDetail = new M_BundlingEvent($this->request);
        $this->entity = new \App\Entities\Bundling();
        $this->baseSubType = $this->model->Pengajuan_Paket;
    }

    public function index()
    {
        $mRule = new M_Rule($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);

        $rule = $mRule->where(['name' => 'Paket', 'isactive' => 'Y'])->first();
        $ruleDetail = [];

        if (!empty($rule))
            $ruleDetail = $mRuleDetail->where('md_rule_id', $rule->md_rule_id)->findAll();

        $data = [
            'today'         => date('d-M-Y'),
            'ref_list' => $ruleDetail,
        ];

        return $this->template->render('transaction/bundling/v_bundling', $data);
    }

    public function showAll()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelect();
            $join = $this->model->getJoin();
            $order = $this->model->column_order;
            $search = $this->model->column_search;
            $sort = $this->model->order;

            $where['md_employee.md_employee_id'] = [
                'value'     => $this->access->getEmployeeData()
            ];

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->{$this->model->primaryKey};

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->documentno;
                $row[] = $value->employee_fullname;
                $row[] = docStatus($value->docstatus);
                $row[] = $value->bundling_type;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = format_dmy($value->startdate, '-') . ' s/d ' . format_dmy($value->enddate, '-');
                $row[] = $value->description;
                $row[] = $value->createdby;
                $row[] = $this->template->tableButton($ID, $value->docstatus, $this->BTN_Print);
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
            $mYear = new M_Year($this->request);
            $mRule = new M_Rule($this->request);
            $mRuleDetail = new M_RuleDetail($this->request);

            $post = $this->request->getVar();
            $post['submissiontype'] = $this->baseSubType;
            $post["necessary"] = 'PK';

            //! Mandatory property for detail validation
            $table = json_decode($post['table']);

            $post['line'] = countLine($table);
            $post['detail'] = [
                'table' => arrTableLine($table)
            ];

            try {
                if (!$this->validation->run($post, 'paket')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $estimateTime = $post['estimate_time'];

                    $rule = $mRule->where([
                        'name'     => 'Paket',
                        'isactive' => 'Y'
                    ])->first();

                    if (!empty($rule)) {
                        $ruleDetail = $mRuleDetail->where(['md_rule_id' => $rule->md_rule_id, 'name' => $post['bundling_type']])->first();

                        if (!empty($ruleDetail)) {
                            $minHour = $ruleDetail->condition;
                            $maxHour = $ruleDetail->description ?? null;

                            // TODO : Checking Period
                            $dateRange = getDatesFromRange($post['startdate'], $post['enddate'], [], 'Y-m-d', 'all');
                            foreach ($dateRange as $date) {
                                $period = $mYear->getPeriodStatus($date, $post['submissiontype'])->getRow();

                                if (empty($period) || $period->period_status == $this->PERIOD_CLOSED) {
                                    break;
                                }
                            }

                            if (empty($period)) {
                                $response = message('success', false, "Periode belum dibuat");
                            } else if ($period->period_status == $this->PERIOD_CLOSED) {
                                $response = message('success', false, "Periode {$period->name} ditutup");
                            } else if (!getOperationResult($estimateTime, $minHour, $ruleDetail->operation)) {
                                $response = message('success', false, "Estimasi jam kurang dari batas minimal jam paket yaitu minimal {$minHour} jam");
                            } else if ($maxHour && $estimateTime >= $maxHour) {
                                $response = message('success', false, "Estimasi jam melebihi ketentuan batas jam paket yaitu maksimal dibawah jam {$maxHour}");
                            } else {
                                $this->entity->fill($post);

                                if ($this->isNew()) {
                                    $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                                    $docNo = $this->model->getInvNumber("submissiontype", $post['submissiontype'], $post);
                                    $this->entity->setDocumentNo($docNo);
                                }

                                $response = $this->save();
                            }
                        } else {
                            $response = message('success', false, 'Master data paket tidak ditemukan');
                        }
                    } else {
                        $response = message('success', false, 'Rule belum dibuat');
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
                $list[0]->setStartDate(format_dmy($list[0]->startdate, "-"));
                $list[0]->setEndDate(format_dmy($list[0]->enddate, "-"));

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($title);
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setList($list);

                $result = [
                    'header'    => $this->field->store($fieldHeader),
                    'line'     => $this->tableLine('update', $detail)
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
        if ($this->request->isAJAX()) {
            $cWfs = new WScenario();
            $mYear = new M_Year($this->request);

            $post = $this->request->getVar();

            $_ID = $post['id'];
            $_DocAction = $post['docaction'];
            $row = $this->model->find($_ID);
            $menu = $this->request->uri->getSegment(2);

            try {
                if (!empty($_DocAction)) {
                    // TODO : Checking Period
                    $dateRange = getDatesFromRange($row->startdate, $row->enddate, [], 'Y-m-d', "all");
                    foreach ($dateRange as $date) {
                        $period = $mYear->getPeriodStatus($date, $row->submissiontype)->getRow();

                        if (empty($period) || $period->period_status == $this->PERIOD_CLOSED) {
                            break;
                        }
                    }

                    if (empty($period)) {
                        $response = message('error', true, "Periode belum dibuat");
                    } else if ($period->period_status == $this->PERIOD_CLOSED) {
                        $response = message('error', true, "Periode {$period->name} ditutup");
                    } else if ($_DocAction === $row->getDocStatus()) {
                        $response = message('error', true, 'Silahkan refresh terlebih dahulu');
                    } else if ($_DocAction === $this->DOCSTATUS_Completed) {
                        $this->message = $cWfs->setScenario($this->entity, $this->model, $this->modelDetail, $_ID, $_DocAction, $menu, $this->session);
                        $response = message('success', true, true);
                    } else if ($_DocAction === $this->DOCSTATUS_Voided) {
                        $this->entity->setDocStatus($this->DOCSTATUS_Voided);
                        $response = $this->save();
                    } else if ($_DocAction === $this->DOCSTATUS_Reopen) {
                        $response = message('error', true, 'Dokumen ini tidak bisa direopen.');
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

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName($this->modelDetail->primaryKey);
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");

        // ? Create
        if (empty($set)) {
            if (!$this->validation->run($post, 'TugasKantorAddRow')) {
                $table = $this->field->errorValidation($this->model->table, $post);
            } else {
                $empList = $this->access->getEmployeeData(true, true);
                $empId = $this->session->get('md_employee_id');

                $whereClause = "md_employee.isactive = 'Y'";
                $whereClause .= " AND md_employee_branch.md_branch_id = {$post['md_branch_id']}";
                $whereClause .= " AND md_employee.md_status_id != {$this->Status_RESIGN}";

                if ($empList) {
                    $whereClause .= " AND md_employee.md_employee_id IN (" . implode(", ", $empList) . ")";
                } else {
                    $whereClause .= " AND (superior_id in (select e.md_employee_id from md_employee e where e.superior_id in (select e.md_employee_id from md_employee e where e.superior_id = $empId))";
                    $whereClause .= " OR md_employee.superior_id IN (SELECT e.md_employee_id FROM md_employee e WHERE e.superior_id = $empId)";
                    $whereClause .= " OR md_employee.superior_id = $empId)";
                }

                $dataEmployee = $employee->getEmployee($whereClause);
                $fieldEmployee->setList($dataEmployee);

                $table = [
                    '',
                    $this->field->fieldTable($fieldEmployee),
                    '',
                    '',
                    $this->field->fieldTable($btnDelete)
                ];
            }
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            $id = $detail[0]->trx_bundling_id;
            $header = $this->model->where('trx_bundling_id', $id)->first();

            // TODO : Get Employee ID
            $empId = $header->md_employee_id;

            // TODO : Get Employee List
            $empList = $this->access->getEmployeeData(true, true);

            foreach ($detail as $row) :
                // TODO : Get Sub Detail Data
                $subDetail = $this->modelSubDetail->where('trx_bundling_participant_id', $row->trx_bundling_participant_id)->first();

                $whereClause = "md_employee.isactive = 'Y'";
                $whereClause .= " AND md_employee_branch.md_branch_id = {$header->md_branch_id}";
                $whereClause .= " AND md_employee.md_employee_id = {$row->md_employee_id}";
                $whereClause .= " AND md_employee.md_status_id != {$this->Status_RESIGN}";

                if ($empList) {
                    $whereClause .= " AND md_employee.md_employee_id IN (" . implode(", ", $empList) . ")";
                } else {
                    $whereClause .= " AND (superior_id in (select e.md_employee_id from md_employee e where e.superior_id in (select e.md_employee_id from md_employee e where e.superior_id = $empId))";
                    $whereClause .= " OR md_employee.superior_id IN (SELECT e.md_employee_id FROM md_employee e WHERE e.superior_id = $empId)";
                    $whereClause .= " OR md_employee.superior_id = $empId)";
                }

                $dataEmployee = $employee->getEmployee($whereClause);
                $fieldEmployee->setList($dataEmployee);

                $fieldEmployee->setValue($row->md_employee_id);
                $fieldEmployee->setAttribute(['data-line-id' => $row->trx_bundling_participant_id, 'data-subdetail' => $subDetail ? 'Y' : 'N']);
                $btnDelete->setValue($row->trx_bundling_participant_id);

                $table[] = [
                    '',
                    $this->field->fieldTable($fieldEmployee),
                    $row->total_time,
                    $row->total_amount,
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        return json_encode($table);
    }

    public function getList()
    {
        $post = $this->request->getVar();

        if ($this->request->isAJAX()) {
            try {
                $employeeID = $post['md_employee_id'];
                $startDate = date('Y-m-d', strtotime($post['startdate']));

                $where = "md_employee_id = {$employeeID}
                          AND (DATE(startdate) <= '{$startDate}'
                          AND DATE(enddate) >= '{$startDate}')
                          AND docstatus = '{$this->DOCSTATUS_Inprogress}'
                          AND isapproved = 'Y'";

                $list_data = array_unique(array_map(fn($val) => [
                    'id' => $val->{$this->model->primaryKey},
                    'text' => $val->documentno . ' - ' . $val->name
                ], $this->model->where($where)->findAll()), SORT_REGULAR);

                $response = array_values($list_data);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function getSubData()
    {
        if ($this->request->isAjax()) {
            $mOvertime = new M_Overtime($this->request);
            $post = $this->request->getVar();

            $tableHtml = '<div class="table-responsive">
                            <table class="table table-hover table-bordered table-responsive">
                                <thead>
                                    <tr>
                                        <th>No Lembur</th>
                                        <th>Tanggal</th>
                                        <th>Total Jam</th>
                                    </tr>
                                </thead>
                                <tbody>';

            $subline = $this->modelSubDetail->where('trx_bundling_participant_id', $post['id'])->orderBy('date', 'ASC')->findAll();

            foreach ($subline as $row) {
                $overtime = $mOvertime->getOvertimeDetail("trx_overtime_detail_id = {$row->trx_overtime_detail_id}")->getRow();
                $tableHtml .= "<tr>
                                <td>{$overtime->documentno}</td>
                                <td>{$row->date}</td>
                                <td>{$row->time}</td>
                                </tr>";
            }

            $tableHtml .=       '</tbody>
                            </table>
                        </div>';

            $response = message('success', true, $tableHtml);

            return $this->response->setJSON($response);
        }
    }
}

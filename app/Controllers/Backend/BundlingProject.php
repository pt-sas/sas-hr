<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Bundling;
use App\Models\M_BundlingEvent;
use App\Models\M_BundlingParticipant;
use App\Models\M_Employee;
use App\Models\M_MasterBundling;
use App\Models\M_Overtime;
use App\Services\BundlingServices;
use Config\Services;

class BundlingProject extends BaseController
{
    protected $baseSubType;

    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Bundling($this->request);
        $this->modelDetail = new M_BundlingParticipant($this->request);
        $this->modelSubDetail = new M_BundlingEvent($this->request);
        $this->entity = new \App\Entities\Bundling();
        $this->baseSubType = 100031;
    }

    public function index()
    {
        $mMasterBundling = new M_MasterBundling($this->request);

        $data = [
            'today'         => date('d-M-Y'),
            'masterBundling' => $mMasterBundling->where(['md_doctype_id' => $this->baseSubType])->findAll(),
        ];

        return $this->template->render('transaction/bundlingproject/v_bundling', $data);
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

            $where['submissiontype'] = $this->baseSubType;

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
            $post = $this->request->getVar();

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
                    //* Set ID Data
                    $post['trx_bundling_id'] = !empty($post['id']) ? $post['id'] : null;

                    $service = new BundlingServices($this->session->get('sys_user_id'), $this->session->get('md_employee_id'), $this->baseSubType);
                    $result = $service->create($post);

                    if ($result && !empty($post['id'])) {
                        $response = message('success', true, "Your data has been updated successfully !");
                    } else if ($result) {
                        $response = message('success', true, "Your data has been inserted successfully !");
                    } else {
                        $response = message('error', false, "No data to Insert");
                    }
                }
            } catch (\App\Exceptions\BaseException $e) {
                $response = message('error', false, $e->getMessage());
                log_message('error', 'Backend/Bundling [create] Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
                log_message('error', 'Backend/Bundling [create] Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
            }

            return $this->response->setJSON($response);
        }
    }

    public function show($id)
    {

        if ($this->request->isAJAX()) {
            try {
                $service = new BundlingServices($this->session->get('sys_user_id'), $this->session->get('md_employee_id'), $this->baseSubType);

                $data = $service->show($id);

                //* Formating for Web
                $title = $data['title'];
                $data['header'][0]->startdate = format_dmy($data['header'][0]->startdate, "-");
                $data['header'][0]->enddate = format_dmy($data['header'][0]->startdate, "-");

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($title);
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setList($data['header']);

                $result = [
                    'header'    => $this->field->store($fieldHeader),
                    'line'     => $this->tableLine('update', $data['detail'])
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
            $post = $this->request->getVar();

            try {
                if (empty($post['docaction']))
                    throw new ValidationException("Silahkan pilih tindakan terlebih dahulu");

                $service = new BundlingServices($this->session->get('sys_user_id'), $this->session->get('md_employee_id'), $this->baseSubType);
                $message = $service->proccessTransaction($post['id'], $post['docaction']);

                $response = message('success', true, $message);
            } catch (\App\Exceptions\BaseException $e) {
                $response = message('error', false, $e->getMessage());
            } catch (\Exception $e) {
                log_message('error', 'Backend/Bundling [processIt] Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
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
                $whereClause .= " AND md_employee_division.md_division_id = {$post['md_division_id']}";
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
                $whereClause .= " AND md_employee_division.md_division_id = {$header->md_division_id}";
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
                          AND docstatus = '{$this->DOCSTATUS_Inprogress}'";

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

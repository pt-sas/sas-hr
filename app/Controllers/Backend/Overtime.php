<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Overtime;
use App\Models\M_OvertimeDetail;
use App\Models\M_AccessMenu;
use Config\Services;
use App\Models\M_Employee;

class Overtime extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Overtime($this->request);
        $this->modelDetail = new M_OvertimeDetail($this->request);
        $this->entity = new \App\Entities\Overtime();
    }

    public function index()
    {

        $data = [
            'today'     => date('d-M-Y')
        ];

        return $this->template->render('transaction/overtime/v_overtime', $data);
    }

    public function showAll()
    {
        $mAccess = new M_AccessMenu($this->request);
        $mEmployee = new M_Employee($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelect();
            $join = $this->model->getJoin();
            $order = $this->model->column_order;
            $search = $this->model->column_search;
            $sort = $this->model->order;

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
                $ID = $value->trx_overtime_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->documentno;
                $row[] = $value->employee_fullname;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = format_dmy($value->startdate, '-');
                $row[] = $value->description;
                $row[] = docStatus($value->docstatus);
                $row[] = $value->createdby;
                $row[] = $this->template->tableButton($ID, $value->docstatus);
                $data[] = $row;
            endforeach;

            $result = [
                'draw'              => $this->request->getPost('draw'),
                'recordsTotal'      => $this->datatable->countAll($table, $select, $order, $sort, $search, $join),
                'recordsFiltered'   => $this->datatable->countFiltered($table, $select, $order, $sort, $search, $join),
                'data'              => $data
            ];

            return $this->response->setJSON($result);
        }
    }

    public function create()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            $table = json_decode($post['table']);

            // // ! Mandatory property for detail validation
            $post['line'] = countLine($table);
            $post['detail'] = [
                'table' => arrTableLine($table)
            ];

            try {
                $this->entity->fill($post);

                if (!$this->validation->run($post, 'lembur')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {

                    if ($this->isNew()) {
                        $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                        $docNo = $this->model->getInvNumber();
                        $this->entity->setDocumentNo($docNo);
                    }

                    $response = $this->save();
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
                $rowEmp = $mEmployee->where($mEmployee->primaryKey, $list[0]->getEmployeeId())->first();
                $detail = $this->modelDetail->where($this->model->primaryKey, $id)->findAll();

                $list = $this->field->setDataSelect($mEmployee->table, $list, $mEmployee->primaryKey, $rowEmp->getEmployeeId(), $rowEmp->getValue());

                $title = $list[0]->getDocumentNo() . "_" . $rowEmp->getFullName();
                $list[0]->setStartDate(format_dmy($list[0]->startdate, "-"));

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($title);
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setField(["datestart", "dateend"]);
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
            $line = $this->modelDetail->where($this->model->primaryKey, $_ID)->first();
            $menu = $this->request->uri->getSegment(2);

            try {
                if (!empty($_DocAction)) {
                    if ($_DocAction === $row->getDocStatus()) {
                        $response = message('error', true, 'Silahkan refresh terlebih dahulu');
                    } else if ($_DocAction === $this->DOCSTATUS_Completed) {
                        if ($line) {
                            $this->message = $cWfs->setScenario($this->entity, $this->model, null, $_ID, $_DocAction, $menu, $this->session);


                            // This for set status to Hold in Overtime Detail
                            $ovt_line = $this->modelDetail->where($this->model->primaryKey, $_ID)->find();

                            foreach ($ovt_line as $key => $value) {
                                $value->status = 'H';
                                $this->modelDetail->save($value);
                            }
                            // 

                            $response = message('success', true, true);
                        } else {
                            $response = message('error', true, 'Line Kosong');
                        }
                    } else if ($_DocAction === $this->DOCSTATUS_Unlock) {
                        $this->entity->setDocStatus($this->DOCSTATUS_Drafted);
                        $response = $this->save();
                    } else if ($_DocAction === $this->DOCSTATUS_Voided) {
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

        $post = $this->request->getPost();

        $table = [];

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

        $fieldDateStart = new \App\Entities\Table();
        $fieldDateStart->setName("datestart");
        $fieldDateStart->setType("text");
        $fieldDateStart->setClass("datepicker");
        $fieldDateStart->setLength(150);
        $fieldDateStart->setIsReadonly(true);

        $fieldDateEnd = new \App\Entities\Table();
        $fieldDateEnd->setName("dateend");
        $fieldDateEnd->setType("text");
        $fieldDateEnd->setClass("datepicker");
        $fieldDateEnd->setLength(150);
        $fieldDateEnd->setIsReadonly(true);

        $fieldDateEndRealization = new \App\Entities\Table();
        $fieldDateEndRealization->setName("dateend_realization");
        $fieldDateEndRealization->setType("text");
        $fieldDateEndRealization->setLength(150);
        $fieldDateEndRealization->setIsReadonly(true);

        $fieldStartTime = new \App\Entities\Table();
        $fieldStartTime->setName("starttime");
        $fieldStartTime->setId("starttime");
        $fieldStartTime->setType("text");
        $fieldStartTime->setClass("timepicker");
        $fieldStartTime->setLength(100);

        $fieldEndTime = new \App\Entities\Table();
        $fieldEndTime->setName("endtime");
        $fieldEndTime->setId("endtime");
        $fieldEndTime->setType("text");
        $fieldEndTime->setClass("timepicker");
        $fieldEndTime->setLength(100);

        $fieldEndTimeRealization = new \App\Entities\Table();
        $fieldEndTimeRealization->setName("endtime_realization");
        $fieldEndTimeRealization->setType("text");
        $fieldEndTimeRealization->setClass("timepicker");
        $fieldEndTimeRealization->setLength(100);
        $fieldEndTimeRealization->setIsReadonly(true);

        $fieldBalance = new \App\Entities\Table();
        $fieldBalance->setName("overtime_balance");
        $fieldBalance->setType("text");
        $fieldBalance->setLength(100);
        $fieldBalance->setIsReadonly(true);

        $fieldExpense = new \App\Entities\Table();
        $fieldExpense->setName("overtime_expense");
        $fieldExpense->setType("text");
        $fieldExpense->setLength(150);
        $fieldExpense->setIsReadonly(true);

        $fieldTotal = new \App\Entities\Table();
        $fieldTotal->setName("total");
        $fieldTotal->setType("text");
        $fieldTotal->setLength(150);
        $fieldTotal->setIsReadonly(true);

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
            if (!$this->validation->run($post, 'lemburAddRow')) {
                $table = $this->field->errorValidation($this->model->table, $post);
            } else {

                if ($post['md_branch_id'] !== null || $post['md_division_id'] !== null) {
                    $dataEmployee = $employee->getEmployee($post['md_branch_id'], $post['md_division_id']);
                    $fieldEmployee->setList($dataEmployee);
                }

                $fieldDateStart->setValue(format_dmy($post['startdate'], "-"));
                $fieldDateEnd->setValue(format_dmy($post['enddate'], "-"));
                $table = [
                    $this->field->fieldTable($fieldEmployee),
                    $this->field->fieldTable($fieldDateStart),
                    $this->field->fieldTable($fieldStartTime),
                    $this->field->fieldTable($fieldDateEnd),
                    $this->field->fieldTable($fieldEndTime),
                    $this->field->fieldTable($fieldDateEndRealization),
                    $this->field->fieldTable($fieldEndTimeRealization),
                    $this->field->fieldTable($fieldBalance),
                    $this->field->fieldTable($fieldExpense),
                    $this->field->fieldTable($fieldTotal),
                    $this->field->fieldTable($fieldDesctiprion),
                    '',
                    $this->field->fieldTable($btnDelete)
                ];
            }
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {

            foreach ($detail as $row) :
                $id = $row->getOvertimeId();
                $header = $this->model->where('trx_overtime_id', $id)->first();

                $dataEmployee = $employee->getEmployee($header->md_branch_id, $header->md_division_id);
                $fieldEmployee->setList($dataEmployee);

                $fieldEmployee->setValue($row->getEmployeeId());
                $fieldDateStart->setValue(format_dmy($row->getStartDate(), '-'));
                $fieldStartTime->setValue(format_time($row->getStartDate()));
                $fieldDateEnd->setValue(format_dmy($row->getEndDate(), '-'));
                $fieldEndTime->setValue(format_time($row->getEndDate()));
                if ($row->getEndDateRealization() != "0000-00-00 00:00:00") {
                    $fieldDateEndRealization->setValue(format_dmy($row->getEndDateRealization(), '-'));
                    $fieldEndTimeRealization->setValue(format_time($row->getEndDateRealization()));
                }
                $fieldDesctiprion->setValue($row->getDescription());
                $fieldBalance->setValue($row->getOvertimeBalance());
                $fieldExpense->setValue(formatRupiah($row->getOvertimeExpense()));
                $fieldTotal->setValue(formatRupiah($row->getTotal()));
                $btnDelete->setValue($row->getOvertimeDetailId());

                if ($header->docstatus == 'IP' || $header->docstatus == 'CO') {
                    $status = statusRealize($row->status);
                } else {
                    $status = '';
                }

                $table[] = [
                    $this->field->fieldTable($fieldEmployee),
                    $this->field->fieldTable($fieldDateStart),
                    $this->field->fieldTable($fieldStartTime),
                    $this->field->fieldTable($fieldDateEnd),
                    $this->field->fieldTable($fieldEndTime),
                    $this->field->fieldTable($fieldDateEndRealization),
                    $this->field->fieldTable($fieldEndTimeRealization),
                    $this->field->fieldTable($fieldBalance),
                    $this->field->fieldTable($fieldExpense),
                    $this->field->fieldTable($fieldTotal),
                    $this->field->fieldTable($fieldDesctiprion),
                    $status,
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        return json_encode($table);
    }
}

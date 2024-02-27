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
            $arrEmployee = $mEmployee->getChartEmployee($this->session->get("md_employee_id"));
            $arrEmployee = implode(",", $arrEmployee);

            $access = $mAccess->getAccess($this->session->get("sys_user_id"));

            if ($access && isset($access["branch"]) && isset($access["division"])) {
                $where['trx_absent.md_branch_id'] = [
                    'value'     => $access["branch"]
                ];

                $where['trx_absent.md_division_id'] = [
                    'value'     => $access["division"]
                ];

                if ($arrEmployee)
                    $where = [
                        '(trx_absent.created_by =' . $this->session->get("sys_user_id") . ' OR trx_absent.md_employee_id IN (' . $arrEmployee . '))'
                    ];
            } else {
                $where['trx_absent.md_branch_id'] = "";
                $where['trx_absent.md_division_id'] = "";
            }

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join);

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
                $row[] = format_dmy($value->startdate,'-');
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
                            $this->entity->setDocStatus($this->DOCSTATUS_Completed);
                            $this->message = $cWfs->setScenario($this->entity, $this->model, $this->modelDetail, $_ID, $_DocAction, $menu, $this->session);
                            $response = message('success', true, $this->message);
                        } else {
                            $this->entity->setDocStatus($this->DOCSTATUS_Invalid);
                            $response = $this->save();
                        }
                    } else if ($_DocAction === $this->DOCSTATUS_Unlock && !$receipt) {
                        $this->entity->setDocStatus($this->DOCSTATUS_Drafted);
                        $response = $this->save();
                    } else if ($receipt && ($_DocAction === $this->DOCSTATUS_Unlock || $_DocAction === $this->DOCSTATUS_Voided)) {
                        $response = message('error', true, 'Cannot be processed');
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

                if($post['md_branch_id'] !== null || $post['md_division_id'] !== null) {
                    $dataEmployee = $employee->getEmployee($post['md_branch_id'],$post['md_division_id']);
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
                    $this->field->fieldTable($fieldDesctiprion),
                    $this->field->fieldTable($btnDelete)
                ];
            }}

         //? Update
         if (!empty($set) && count($detail) > 0) {
            
            foreach ($detail as $row) :
                $id = $row->getOvertimeId();
                $header = $this->model->where('trx_overtime_id', $id)->first();

                $dataEmployee = $employee->getEmployee($header->md_branch_id,$header->md_division_id);
                $fieldEmployee->setList($dataEmployee);
                
                $fieldEmployee->setValue($row->getEmployeeId());
                $fieldDateStart->setValue(format_dmy($row->getStartDate(),'-'));
                $fieldStartTime->setValue(format_time($row->getStartDate()));
                $fieldDateEnd->setValue(format_dmy($row->getEndDate(),'-'));
                $fieldEndTime->setValue(format_time($row->getEndDate()));
                $btnDelete->setValue($row->getOvertimeDetailId());

                $table[] = [
                    $this->field->fieldTable($fieldEmployee),
                    $this->field->fieldTable($fieldDateStart),
                    $this->field->fieldTable($fieldStartTime),
                    $this->field->fieldTable($fieldDateEnd),
                    $this->field->fieldTable($fieldEndTime),
                    $this->field->fieldTable($fieldDesctiprion),
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        return json_encode($table);
    }
  
}
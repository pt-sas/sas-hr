<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Overtime;
use App\Models\M_OvertimeDetail;
use Config\Services;
use App\Models\M_Employee;
use App\Models\M_AllowanceAtt;

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
        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelect();
            $join = $this->model->getJoin();
            $order = $this->model->column_order;
            $search = $this->model->column_search;
            $sort = $this->model->order;

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

            //! Mandatory property for detail validation
            $post['line'] = countLine($table);
            $post['detail'] = [
                'table' => arrTableLine($table)
            ];
            
            $post["startdate"] = date('Y-m-d', strtotime($post["datestart"])) . " " . $post['starttime'];
            $post["enddate"] = date('Y-m-d', strtotime($post["dateend"])) . " " . $post['endtime'];

            try {
                $this->entity->fill($post);

                if (!$this->validation->run($post, 'pengajuantugas')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {

                    if ($this->isNew()) {
                        $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                        $docNo = $this->model->getInvNumber("submissiontype", $this->Pengajuan_Lembur);
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

                $list = $this->field->setDataSelect($mEmployee->table, $list, $mEmployee->primaryKey, $rowEmp->getEmployeeId(), $rowEmp->getValue());

                $title = $list[0]->getDocumentNo() . "_" . $rowEmp->getFullName();

                //Need to set data into date field in form
                $list[0]->starttime = format_time($list[0]->startdate);
                $list[0]->endtime = format_time($list[0]->enddate);
                $list[0]->datestart = format_dmy($list[0]->startdate, "-");
                $list[0]->dateend = format_dmy($list[0]->enddate, "-");



                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($title);
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setField(["starttime", "endtime", "datestart", "dateend"]);
                $fieldHeader->setList($list);

                $result = [
                    'header'    => $this->field->store($fieldHeader)
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
        $mAllowance = new M_AllowanceAtt($this->request);

        if ($this->request->isAJAX()) {
            $post = $this->request->getVar();

            $_ID = $post['id'];
            $_DocAction = $post['docaction'];

            $row = $this->model->find($_ID);

            try {
                if (!empty($_DocAction)) {
                    if ($_DocAction === $row->getDocStatus()) {
                        $response = message('error', true, 'Silahkan refresh terlebih dahulu');
                    } else if ($_DocAction === $this->DOCSTATUS_Completed) {
                        $this->entity->setDocStatus($this->DOCSTATUS_Completed);
                        $response = $this->save();

                        $range = getDatesFromRange($row->getStartDate(), $row->getEndDate());

                        $arr = [];
                        foreach ($range as $date) {
                            $arr[] = [
                                "record_id"         => $_ID,
                                "table"             => $this->model->table,
                                "submissiontype"    => $row->getSubmissionType(),
                                "submissiondate"    => $date,
                                "md_employee_id"    => $row->getEmployeeId(),
                                "amount"            => 0,
                                "created_by"        => $this->access->getSessionUser(),
                                "updated_by"        => $this->access->getSessionUser(),
                            ];
                        }

                        $mAllowance->builder->insertBatch($arr);
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
        $post = $this->request->getVar();
        $employee = new M_Employee($this->request);

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
        $dataEmployee = $employee->where('isactive', 'Y')
            ->orderBy('fullname', 'ASC')
            ->findAll();
        $fieldEmployee->setList($dataEmployee);
        $fieldEmployee->setLength(200);

        $fieldDateStart = new \App\Entities\Table();
        $fieldDateStart->setName("datestart");
        $fieldDateStart->setId("datestart");
        $fieldDateStart->setType("text");
        $fieldDateStart->setClass("datepicker");
        $fieldDateStart->setLength(150);

        $fieldDateEnd = new \App\Entities\Table();
        $fieldDateEnd->setName("dateend");
        $fieldDateEnd->setId("dateend");
        $fieldDateEnd->setType("text");
        $fieldDateEnd->setClass("date-end");
        $fieldDateEnd->setLength(150);

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
        $fieldEndTime->setClass("timepicker-end");
        $fieldEndTime->setLength(100);

        $fieldDesctiprion = new \App\Entities\Table();
        $fieldDesctiprion->setName("description");
        $fieldDesctiprion->setId("description");
        $fieldDesctiprion->setType("text");
        $fieldDesctiprion->setLength(250);

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName("Kuda");
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");
        
        // ? Create

        if (empty($set)) {
            // if (!$this->validation->run($post, 'lemburAddRow')) {
            //     $table = $this->field->errorValidation($this->model->table, $post);
            // } else {

                $table = [
                    $this->field->fieldTable($fieldEmployee),
                    $this->field->fieldTable($fieldDateStart),
                    $this->field->fieldTable($fieldStartTime),
                    $this->field->fieldTable($fieldDateEnd),
                    $this->field->fieldTable($fieldEndTime),
                    $this->field->fieldTable($fieldDesctiprion),
                    $this->field->fieldTable($btnDelete)
                ];
            }
        // }

        //? Update
        // if (!empty($set) && count($detail) > 0) {
        //     foreach ($detail as $row) :
        //         $valPro = $product->find($row->md_product_id);

        //         $table[] = [
        //             $this->field->fieldTable('input', 'text', 'md_product_id', 'text-uppercase', 'required', 'readonly', null, null, $valPro->getName(), 300),
        //             $this->field->fieldTable('input', 'text', 'qtyentered', 'number', 'required', null, null, null, $row->qtyentered, 70),
        //             $this->field->fieldTable('input', 'text', 'unitprice', 'rupiah', 'required', null, null, null, $row->unitprice, 125),
        //             $this->field->fieldTable('input', 'text', 'lineamt', 'rupiah', 'required', 'readonly', null, null, $row->lineamt, 125),
        //             $this->field->fieldTable('input', 'checkbox', 'isspare', null, null, 'readonly', null, null, $row->isspare),
        //             $this->field->fieldTable('select', null, 'md_employee_id', null, 'required', !empty($row->md_employee_id) ? 'readonly' : null, null, $dataEmployee, $row->md_employee_id, 200, 'md_employee_id', 'name'),
        //             $this->field->fieldTable('input', 'text', 'specification', null, null, null, null, null, $row->specification, 250),
        //             $this->field->fieldTable('input', 'text', 'description', null, null, null, null, null, $row->description, 250),
        //             $this->field->fieldTable('button', 'button', 'trx_quotation_detail_id', null, null, null, null, null, $row->trx_quotation_detail_id)
        //         ];
        //     endforeach;
        // }

        return json_encode($table);
    }

}
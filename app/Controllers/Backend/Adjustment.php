<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Adjustment;
use App\Models\M_AllowanceAtt;
use App\Models\M_DocumentType;
use App\Models\M_Employee;
use App\Models\M_LeaveBalance;
use App\Models\M_Year;
use Config\Services;

class Adjustment extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Adjustment($this->request);
        $this->entity = new \App\Entities\Adjustment();
    }

    public function index()
    {
        $mDocType = new M_DocumentType($this->request);
        $data = [
            'today'     => date('d-M-Y'),
            'type'      => $mDocType->whereIn('md_doctype_id', [$this->model->Pengajuan_Adj_Cuti, $this->model->Pengajuan_Adj_TKH])->findAll()
        ];

        return $this->template->render('transaction/adjustment/v_adjustment', $data);
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
                $row[] = docStatus($value->docstatus);
                $row[] = $value->employee_fullname;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = format_dmy($value->date, '-');
                $row[] = $value->reason;
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
            $post = $this->request->getVar();
            $post["necessary"] = $post['submissiontype'] == $this->model->Pengajuan_Adj_Cuti ? 'AC' : 'AT';

            try {
                if (!$this->validation->run($post, 'penyesuaian')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    // TODO : Checking Period
                    $period = $mYear->getPeriodStatus(date('Y-m-d', strtotime($post['date'])), $post['submissiontype'])->getRow();

                    if (empty($period)) {
                        $response = message('success', false, "Periode belum dibuat");
                    } else if ($period->period_status == $this->PERIOD_CLOSED) {
                        $response = message('success', false, "Periode {$period->name} ditutup");
                    } else if ($post['submissiontype'] == $this->model->Pengajuan_Adj_Cuti && $post['ending_balance'] < 0) {
                        $response = message('success', false, 'Saldo akhir cuti tidak bisa dibawah 0');
                    } else {
                        $this->entity->fill($post);

                        if ($this->isNew()) {
                            $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                            $docNo = $this->model->getInvNumber("submissiontype", $post['submissiontype'], $post);
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
                $rowEmp = $mEmployee->where($mEmployee->primaryKey, $list[0]->getEmployeeId())->first();

                $list = $this->field->setDataSelect($mEmployee->table, $list, $mEmployee->primaryKey, $rowEmp->getEmployeeId(), $rowEmp->getValue());

                $title = $list[0]->getDocumentNo() . "_" . $rowEmp->getFullName();
                $list[0]->setDate(format_dmy($list[0]->date, "-"));

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($title);
                $fieldHeader->setTable($this->model->table);
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
                    $period = $mYear->getPeriodStatus(date('Y-m-d', strtotime($row->date)), $row->submissiontype)->getRow();

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

    public function getBeginBalance()
    {
        $mLeavebalance = new M_LeaveBalance($this->request);
        $mAllowance = new M_AllowanceAtt($this->request);

        if ($this->request->isAjax()) {
            $post = $this->request->getPost();

            try {
                $submissionType = $post['submissiontype'];
                $md_employee_id = $post['md_employee_id'];
                $date = date('Y-m-d', strtotime($post['date']));

                if ($submissionType == $this->model->Pengajuan_Adj_Cuti) {
                    $year = date('Y', strtotime($post['date']));
                    $where = "md_employee_id = {$md_employee_id}";
                    $where .= " AND year = '{$year}'";

                    $begin_balance = (int) $mLeavebalance->getBalance($where)->balance_amount;
                } else if ($submissionType == $this->model->Pengajuan_Adj_TKH) {
                    $begin_balance = $mAllowance->getTotalAmount($md_employee_id, $date);
                }

                $response = ['data' => $begin_balance];
            } catch (\Throwable $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}

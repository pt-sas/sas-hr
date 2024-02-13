<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use Config\Services;
use App\Models\M_Absent;
use App\Models\M_Employee;
use App\Models\M_Reference;
use App\Models\M_AllowanceAtt;
use App\Models\M_Rule;
use App\Models\M_RuleDetail;

class PermissionLeaveEarly extends BaseController
{
    /** Pengajuan Ijin Pulang Cepat */
    protected $Pengajuan_Pulang_Cepat = 'pulang cepat';

    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Absent($this->request);
        $this->entity = new \App\Entities\Absent();
    }

    public function index()
    {
        $mReference = new M_Reference($this->request);

        $data = [
            'today'     => date('d-M-Y')
        ];

        return $this->template->render('transaction/permission/leaveearly/v_permission_leave_early', $data);
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
            $where['trx_absent.submissiontype'] = $this->Pengajuan_Pulang_Cepat;

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
                $row[] = format_dmytime($value->startdate, '-');
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
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            $post["submissiontype"] = $this->Pengajuan_Pulang_Cepat;
            $post["necessary"] = $this->Form_Kelengkapan_Absent;
            $post["startdate"] = date('Y-m-d', strtotime($post["datestart"])) . " " . $post['starttime'];

            try {
                $this->entity->fill($post);

                if (!$this->validation->run($post, 'pengajuan')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {

                    if ($this->isNew()) {
                        $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                        $docNo = $this->model->getInvNumber("submissiontype", $this->Pengajuan_Pulang_Cepat, $post["necessary"]);
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
                $list[0]->datestart = format_dmy($list[0]->startdate, "-");

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($title);
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setField(["starttime", "datestart"]);
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
        $mRule = new M_Rule($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);

        if ($this->request->isAJAX()) {
            $post = $this->request->getVar();

            $_ID = $post['id'];
            $_DocAction = $post['docaction'];
            $_Data = $this->model->where('trx_absent_id', $post['id'])->find();
            $_Rule = $mRule->where('name', 'Pulang Cepat')->find();
            $_RuleDetail = $mRuleDetail->where('md_rule_id = ' . $_Rule[0]->md_rule_id)->find();
            $jamMasuk = convertToMinutes(format_time('08:00'));
            $sore = ($jamMasuk + $_RuleDetail[0]->condition);
            $siang = ($jamMasuk + $_RuleDetail[1]->condition);
            $jam = convertToMinutes(format_time($_Data[0]->startdate));

            $row = $this->model->find($_ID);

            try {
                if (!empty($_DocAction)) {
                    if ($_DocAction === $row->getDocStatus()) {
                        $response = message('error', true, 'Silahkan refresh terlebih dahulu');
                    } else if ($_DocAction === $this->DOCSTATUS_Completed) {
                        $this->entity->setDocStatus($this->DOCSTATUS_Completed);
                        $response = $this->save();

                        $amount = 0;
                        
                        // // Check Rule
                         if($_Rule[0]->isdetail === 'Y') {
                            if (getOperationResult($jam,$jamMasuk,$_RuleDetail[0]->operation) === true) {
                                $amount = 0;
                            }
                            else if(getOperationResult($jam,$siang,$_RuleDetail[1]->operation) === true) {
                                $amount = abs($_RuleDetail[1]->value);
                            }
                            else if(getOperationResult($jam,$sore,$_RuleDetail[0]->operation) === true) {
                                $amount = abs($_RuleDetail[0]->value);
                            }
                        }
                        
                        if($amount != 0) {
                        $arr[] = [
                            "record_id"         => $_ID,
                            "table"             => $this->model->table,
                            "submissiontype"    => $row->getSubmissionType(),
                            "submissiondate"    => $row->getStartDate(),
                            "md_employee_id"    => $row->getEmployeeId(),
                            "amount"            => $amount,
                            "created_by"        => $this->access->getSessionUser(),
                            "updated_by"        => $this->access->getSessionUser(),
                        ];

                        $mAllowance->builder->insertBatch($arr);
                        }
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
}
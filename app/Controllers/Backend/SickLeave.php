<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use Config\Services;
use App\Models\M_Absent;
use App\Models\M_Employee;
use App\Models\M_AllowanceAtt;
use App\Models\M_Rule;
use App\Models\M_RuleDetail;

use function PHPUnit\Framework\isNull;

class SickLeave extends BaseController
{
    /** Pengajuan Sakit */
    protected $Pengajuan_Sakit = 'sakit';

    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Absent($this->request);
        $this->entity = new \App\Entities\Absent();
    }

    public function index()
    {

        $data = [
            'today'     => date('d-M-Y')
        ];

        return $this->template->render('transaction/sickleave/v_sickleave', $data);
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
            $where['trx_absent.submissiontype'] = $this->Pengajuan_Sakit;

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
        $mEmployee = new M_Employee($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();
            $file = $this->request->getFile('image');

            $post["submissiontype"] = $this->Pengajuan_Sakit;
            $post["necessary"] = $this->Form_Absent;

            try {
                $img_name = "";

                if ($file && $file->isValid()) {
                    $row = $mEmployee->find($post['md_employee_id']);

                    $ext = $file->getClientExtension();
                    $lenPos = strpos($row->getValue(), '-');
                    $value = substr_replace($row->getValue(), "", $lenPos);
                    $ymd = date('Ymd', strtotime($post['submissiondate']));

                    $img_name = $this->Pengajuan_Sakit . '_' . $value . '_' . $ymd . '.' . $ext;
                    $post['image'] = $img_name;
                }

                if (!$this->validation->run($post, 'absent')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $path = $this->PATH_UPLOAD . $this->PATH_Pengajuan . '/';

                    if ($this->isNew()) {
                        uploadFile($file, $path, $img_name);
                    } else {
                        $row = $this->model->find($this->getID());

                        if (!empty($row->getImage()) && $post['image'] !== $row->getImage()) {
                            if (file_exists($path . $row->getImage())) {
                                unlink($path . $row->getImage());
                                $file->move($path);
                            }
                        }
                    }

                    $this->entity->fill($post);

                    if ($this->isNew()) {
                        $this->entity->setDocStatus($this->DOCSTATUS_Drafted);
                        $docNo = $this->model->getInvNumber("submissiontype", $this->Pengajuan_Sakit, $post["necessary"]);
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

                $path = 'uploads/' . $this->PATH_Pengajuan . '/';
                $list[0]->image = $path . $list[0]->image;

                $list = $this->field->setDataSelect($mEmployee->table, $list, $mEmployee->primaryKey, $rowEmp->getEmployeeId(), $rowEmp->getValue());

                $title = $list[0]->getDocumentNo() . "_" . $rowEmp->getFullName();

                //Need to set data into date field in form
                $list[0]->startdate = format_dmy($list[0]->startdate, "-");
                $list[0]->enddate = format_dmy($list[0]->enddate, "-");

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
        $mRule = new M_Rule($this->request);
        $mAllowance = new M_AllowanceAtt($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);

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
                        
                        $_Rule = $mRule->where(['name' => 'Sakit', 'isactive' => 'Y'])->find();
                        $_RuleDetail = $mRuleDetail->where('md_rule_id = ' . $_Rule[0]->md_rule_id)->find();
                        $amount = 0;
                        $amountimage = abs($_RuleDetail[0]->value);

                        // // check rule
                        // if($_Rule[0]->isdetail === 'Y') {

                        // } else if ($_Rule[0]->isdetail ==='N') {
                                if($_Rule[0]->condition === "") {
                                    $amount = abs($_Rule[0]->value);}
                        //         } else {  }
                        // };

                        $range = getDatesFromRange($row->getStartDate(), $row->getEndDate());

                        $arr = [];
                        foreach ($range as $date) {
                            $arr[] = [
                                "record_id"         => $_ID,
                                "table"             => $this->model->table,
                                "submissiontype"    => $row->getSubmissionType(),
                                "submissiondate"    => $date,
                                "md_employee_id"    => $row->getEmployeeId(),
                                "amount"            => $amount,
                                "created_by"        => $this->access->getSessionUser(),
                                "updated_by"        => $this->access->getSessionUser(),
                            ];
                        }
                        
                        $mAllowance->builder->insertBatch($arr);

                        if($row->image === "") {
                            $arrImg[] = [
                                "record_id"         => $_ID,
                                "table"             => $this->model->table,
                                "submissiontype"    => $row->getSubmissionType(),
                                "submissiondate"    => $date,
                                "md_employee_id"    => $row->getEmployeeId(),
                                "amount"            => $amountimage,
                                "created_by"        => $this->access->getSessionUser(),
                                "updated_by"        => $this->access->getSessionUser(),
                            ];
                            $mAllowance->builder->insertBatch($arrImg);
                        }


                        // $response = $row;
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
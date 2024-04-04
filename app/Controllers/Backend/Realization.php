<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use App\Models\M_AbsentDetail;
use Config\Services;

class Realization extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Absent($this->request);
    }

    public function index()
    {
        $start_date = format_dmy(date('Y-m-d', strtotime('- 1 days')), "-");
        $end_date = format_dmy(date('Y-m-d'), "-");

        $data = [
            'date_range'            => $start_date . ' - ' . $end_date,
            'toolbarRealization'    => $this->template->toolbarButtonProcess()
        ];

        return $this->template->render('transaction/realization/v_realization', $data);
    }

    public function showAll()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $list = $this->model->getSelectDetail();
            // $join = $this->model->getJoinDetail();
            $order = $this->request->getPost('columns');
            $search = $this->request->getPost('search');
            $sort = [];
            // $sort = ['trx_absent_detail.date' => 'ASC'];

            // $where['trx_absent.docstatus'] = $this->DOCSTATUS_Inprogress;
            // $where['trx_absent_detail.isagree'] = 'H';

            $data = [];

            $fieldChk = new \App\Entities\Table();
            $fieldChk->setName("ischecked");
            $fieldChk->setType("checkbox");
            $fieldChk->setClass("check-realize");

            $number = $this->request->getPost('start');
            // $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);
            // $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_absent_detail_id;

                $number++;

                $reason = $value->reason;

                if (!empty($value->leavetype))
                    $reason = "<span class='badge badge-info' id=" . $value->md_leavetype_id . ">" . $value->leavetype . "</span>" . " - " . $value->reason;

                // $row[] = $this->field->fieldTable($fieldChk);
                $row[] = $number;
                $row[] = $value->submissiontype;
                $row[] = $value->employee_fullname;
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = format_dmy($value->date, '-');
                $row[] = $reason;
                $row[] = $this->template->tableButtonProcess($ID, $value->leavetype);
                $data[] = $row;
            endforeach;

            $recordsTotal = count($data);
            $recordsFiltered = count($data);

            $result = [
                'draw'              => $this->request->getPost('draw'),
                // 'recordsTotal'      => $this->datatable->countAll($table, $select, $order, $sort, $search, $join, $where),
                'recordsTotal'      => $recordsTotal,
                // 'recordsFiltered'   => $this->datatable->countFiltered($table, $select, $order, $sort, $search, $join, $where),
                'recordsFiltered'   => $recordsFiltered,
                'data'              => $data
            ];

            return $this->response->setJSON($result);
        }
    }

    public function create()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            $agree = 'Y';
            $notAgree = 'N';
            $holdAgree = 'H';

            $isAgree = $post['isagree'];
            $submissionDate = $post['submissiondate'];
            $today = date('Y-m-d');
            $todayTime = date('Y-m-d H:i:s');
            $leaveTypeId = $post['md_leavetype_id'];

            try {
                if (!$this->validation->run($post, 'realisasi_agree') && $isAgree === 'Y') {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else if (!$this->validation->run($post, 'realisasi_not_agree') && $isAgree === 'N') {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    if ($isAgree === $agree) {
                        $this->model = new M_AbsentDetail($this->request);
                        $this->entity = new \App\Entities\AbsentDetail();

                        $line = $this->model->find($post['id']);

                        if (empty($leaveTypeId)) {
                            $this->entity->isagree = $isAgree;
                            $response = $this->save();
                        } else {
                            $list = $this->model->where('trx_absent_id', $line->trx_absent_id)->findAll();

                            $arr = [];

                            foreach ($list as $row) {
                                $arr[] = [
                                    "trx_absent_detail_id" => $row->trx_absent_detail_id,
                                    "isagree"           => "Y",
                                    "updated_by"        => $this->session->get('sys_user_id')
                                ];
                            }

                            $this->model->builder->updateBatch($arr, $this->model->primaryKey);
                            $this->message = notification("updated");
                            $response = message('success', true, $this->message);
                        }
                    }

                    if ($isAgree === $notAgree) {
                        $this->model = new M_AbsentDetail($this->request);
                        $line = $this->model->find($post['foreignkey']);

                        $this->model = new M_Absent($this->request);
                        $this->entity = new \App\Entities\Absent();

                        $row = $this->model->find($line->trx_absent_id);

                        /**
                         * Insert Pengajuan baru
                         */
                        $necessary = '';
                        if ($post['submissiontype'] === 'ijin') {
                            $necessary = 'IJ';
                            $this->entity->setNecessary($necessary);
                            $this->entity->setSubmissionType($post['submissiontype']);
                        }

                        if ($post['submissiontype'] === 'alpa') {
                            $necessary = 'AL';
                            $this->entity->setNecessary($necessary);
                            $this->entity->setSubmissionType('alpa');
                        }

                        $this->entity->setEmployeeId($row->getEmployeeId());
                        $this->entity->setNik($row->getNik());
                        $this->entity->setBranchId($row->getBranchId());
                        $this->entity->setDivisionId($row->getDivisionId());
                        $this->entity->setReceivedDate($todayTime);
                        $this->entity->setReason($post['reason']);
                        $this->entity->setSubmissionDate($today);
                        $this->entity->setStartDate(date('Y-m-d', strtotime($submissionDate)));
                        $this->entity->setEndDate(date('Y-m-d', strtotime($submissionDate)));
                        $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                        $post['submissiondate'] = $this->entity->getSubmissionDate();
                        $post['necessary'] = $necessary;

                        $docNo = $this->model->getInvNumber("submissiontype", $post['submissiontype'], $post);
                        $this->entity->setDocumentNo($docNo);
                        $this->isNewRecord = true;

                        $response = $this->save();

                        //* Foreignkey id 
                        $ID =  $this->insertID;

                        $this->model = new M_AbsentDetail($this->request);
                        $this->entity = new \App\Entities\AbsentDetail();

                        $this->entity->isagree = $agree;
                        $this->entity->trx_absent_id = $ID;
                        $this->entity->lineno = 1;
                        $this->entity->date = date('Y-m-d', strtotime($submissionDate));
                        $this->save();

                        //* Foreignkey id
                        $lineID = $this->insertID;

                        /**
                         * Update Pengajuan lama
                         */
                        $this->isNewRecord = false;

                        $this->model = new M_AbsentDetail($this->request);
                        $this->entity = new \App\Entities\AbsentDetail();
                        $this->entity->isagree = $isAgree;
                        $this->entity->trx_absent_detail_id = $post['foreignkey'];
                        $this->entity->ref_absent_detail_id = $lineID;
                        $this->save();

                        /**
                         * Update Pengajuan ref absent detail
                         */
                        $this->model = new M_AbsentDetail($this->request);
                        $this->entity = new \App\Entities\AbsentDetail();
                        $this->entity->ref_absent_detail_id = $post['foreignkey'];
                        $this->entity->trx_absent_detail_id = $lineID;
                        $this->save();

                        $this->model = new M_Absent($this->request);
                        $this->entity = new \App\Entities\Absent();
                        $this->entity->setDocStatus($this->DOCSTATUS_Completed);
                        $this->entity->setAbsentId($ID);
                        $this->save();
                    }

                    $this->model = new M_AbsentDetail($this->request);
                    $list = $this->model->where([
                        'isagree'       => $holdAgree,
                        'trx_absent_id' => $line->trx_absent_id
                    ])->first();

                    if (is_null($list)) {
                        $this->model = new M_Absent($this->request);
                        $this->entity = new \App\Entities\Absent();

                        $this->entity->setDocStatus($this->DOCSTATUS_Completed);
                        $this->entity->setReceivedDate($todayTime);
                        $this->entity->setAbsentId($line->trx_absent_id);
                        $this->save();
                    }
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            // return $this->response->setJSON($response);
            return json_encode($response);
        }
    }

    public function getList()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->getVar();

            $response = [];

            try {
                // if (isset($post['search'])) {
                //     $list = $this->model->where('isactive', 'Y')
                //         ->like('name', $post['search'])
                //         ->orderBy('name', 'ASC')
                //         ->findAll();
                // } else {
                //     $list = $this->model->where('isactive', 'Y')
                //         ->orderBy('name', 'ASC')
                //         ->findAll();
                // }

                $list = [
                    [
                        'id'    => 'alpa',
                        'name'  => 'alpa'
                    ],
                    [
                        'id'    => 'ijin',
                        'name'  => 'ijin'
                    ],
                ];

                foreach ($list as $key => $row) :
                    $response[$key]['id'] = $row['id'];
                    $response[$key]['text'] = $row['name'];
                endforeach;
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}

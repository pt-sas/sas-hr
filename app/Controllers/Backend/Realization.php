<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use App\Models\M_AbsentDetail;
use App\Models\M_Overtime;
use App\Models\M_OvertimeDetail;
use App\Models\M_Rule;
use App\Models\M_RuleDetail;
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

    public function indexOvertime()
    {
        $start_date = format_dmy(date('Y-m-d', strtotime('- 1 days')), "-");
        $end_date = format_dmy(date('Y-m-d'), "-");

        $data = [
            'date_range'            => $start_date . ' - ' . $end_date,
            'toolbarRealization'    => $this->template->toolbarButtonProcess()
        ];

        return $this->template->render('transaction/overtimerealization/v_overtime_realization', $data);
    }

    public function showAll()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelectDetail();
            $join = $this->model->getJoinDetail();
            $order = $this->request->getPost('columns');
            $search = $this->request->getPost('search');
            $sort = ['trx_absent_detail.date' => 'ASC'];

            $where['trx_absent.docstatus'] = $this->DOCSTATUS_Inprogress;
            $where['trx_absent_detail.isagree'] = 'H';

            $data = [];

            $fieldChk = new \App\Entities\Table();
            $fieldChk->setName("ischecked");
            $fieldChk->setType("checkbox");
            $fieldChk->setClass("check-realize");

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_absent_detail_id;

                $number++;

                // $row[] = $this->field->fieldTable($fieldChk);
                $row[] = $number;
                $row[] = $value->submissiontype;
                $row[] = $value->employee_fullname;
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = format_dmy($value->date, '-');
                $row[] = $this->template->tableButtonProcess($ID);
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

    public function showAllOvertime()
    {
        $mOvertime = new M_Overtime($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $table = $mOvertime->table;
            $select = $mOvertime->getSelectDetail();
            $join = $mOvertime->getJoinDetail();
            $order = $this->request->getPost('columns');
            $search = $this->request->getPost('search');
            $sort = ['trx_overtime.documentno' => 'ASC'];

            $where['trx_overtime.docstatus'] = $this->DOCSTATUS_Inprogress;
            $where['trx_overtime_detail.status'] = 'H';

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_overtime_detail_id;

                $number++;

                $row[] = $number;
                $row[] = $value->documentno;
                $row[] = $value->employee_name;
                $row[] = $value->branch_name;
                $row[] = $value->division_name;
                $row[] = format_dmy($value->startdate_line, '-');
                $row[] = format_dmy($value->enddate_line, '-');
                $row[] = format_time($value->startdate_line);
                $row[] = format_time($value->enddate_line);
                $row[] = $this->template->tableButtonProcess($ID);
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

            $agree = 'Y';
            $notAgree = 'N';
            $holdAgree = 'H';

            $isAgree = $post['isagree'];
            $submissionDate = $post['submissiondate'];
            $today = date('Y-m-d');
            $todayTime = date('Y-m-d H:i:s');

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

                        $this->entity->isagree = $isAgree;
                        $response = $this->save();
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

            // return $this->response->setJSON($this->entity);

            return json_encode($response);
        }
    }

    public function createOvertime()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            $agree = 'Y';
            $notAgree = 'N';
            $holdAgree = 'H';

            $isAgree = $post['isagree'];
            $today = date('Y-m-d');
            $todayTime = date('Y-m-d H:i:s');

            try {
                if (!$this->validation->run($post, 'realisasi_lembur_agree') && $isAgree === 'Y') {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $mOvertimeDetail = new M_OvertimeDetail($this->request);
                    $oEntityDetail = new \App\Entities\OvertimeDetail();

                    $line = $mOvertimeDetail->find($post['id']);

                    if ($isAgree === $agree) {

                        $startdate = date('Y-m-d', strtotime($line->startdate)) . " " . $post['starttime'];
                        $enddate =   date('Y-m-d', strtotime($post["enddate"])) . " " . $post['endtime'];
                        $ovt = $this->getHourOvertime($startdate, $enddate);

                        $oEntityDetail->trx_overtime_detail_id = $post['id'];
                        $oEntityDetail->startdate = $startdate;
                        $oEntityDetail->enddate = $enddate;
                        $oEntityDetail->status = $isAgree;
                        $oEntityDetail->overtime_expense = $ovt['expense'];
                        $oEntityDetail->overtime_balance = $ovt['balance'];
                        $oEntityDetail->total = $ovt['total'];

                        $response = $mOvertimeDetail->save($oEntityDetail);
                    }

                    if ($isAgree === $notAgree) {

                        $oEntityDetail->trx_overtime_detail_id = $post['id'];
                        $oEntityDetail->status = $isAgree;

                        $response = $mOvertimeDetail->save($oEntityDetail);
                    }

                    $list = $mOvertimeDetail->where([
                        'status'       => $holdAgree,
                        'trx_overtime_id' => $line->trx_overtime_id
                    ])->first();

                    if (is_null($list)) {
                        $mOvertime = new M_Overtime($this->request);
                        $oEntity = new \App\Entities\Overtime();

                        $oEntity->setOvertimeId($line->trx_overtime_id);
                        $oEntity->setDocStatus($this->DOCSTATUS_Completed);
                        $mOvertime->save($oEntity);
                    }
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

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

    public function getHourOvertime($startdate, $endate)
    {
        $mRule = new M_Rule($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);

        $start = format_dmy($startdate, '-');
        $end = format_dmy($endate, '-');

        $rule = $mRule->where([
            'name'      => 'Lembur',
            'isactive'  => 'Y'
        ])->first();

        $detail = $mRuleDetail->where('md_rule_id', $rule->md_rule_id)->find();

        $data = [];

        // if ($start = $end) {
        $starttime = format_time($startdate);
        $endtime = format_time($endate);

        $startMinutes = convertToMinutes(format_time($starttime));
        $endMinutes = convertToMinutes(format_time($endtime));

        $balance = ($endMinutes - $startMinutes) / 60;
        $total = $detail[0]->value * (int) $balance;


        $data['balance'] = (int) $balance;
        $data['expense'] = $detail[0]->value;
        $data['total'] = $total;
        // } else if ($start < $end) {
        //     $starttime = format_time($startdate);
        //     $endtime = format_time($endate);

        //     $startMinutes = convertToMinutes(format_time($starttime));
        //     $endMinutes = convertToMinutes(format_time($endtime));

        //     $balance = ($endMinutes - $startMinutes) / 60;
        //     $total = $detail[0]->value * (int) $balance;


        //     $data['balance'] = (int) $balance;
        //     $data['expense'] = $detail[0]->value;
        //     $data['total'] = $total;
        // }

        return $data;
    }
}

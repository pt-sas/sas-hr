<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use Config\Services;
use App\Models\M_Employee;
use App\Models\M_AccessMenu;
use App\Models\M_Probation;
use App\Models\M_ProbationDetail;
use App\Models\M_Question;
use App\Models\M_QuestionGroup;
use App\Models\M_Reference;

class EvaluationProbation extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Probation($this->request);
        $this->modelDetail = new M_ProbationDetail($this->request);
        $this->entity = new \App\Entities\Probation();
    }

    public function index()
    {
        $mQuestGroup = new M_QuestionGroup($this->request);
        $mQuestion = new M_Question($this->request);
        $mReference = new M_Reference($this->request);

        $data = [
            'today'     => date('d-M-Y'),
            'quest_group' => $mQuestGroup->where(["menu_url" => 'evaluasi-percobaan', "isactive" => "Y"])->findAll(),
            'question' => $mQuestion->getQuestion(["md_question_group.menu_url" => 'evaluasi-percobaan', "md_question_group.isactive" => "Y", "md_question.isactive" => "Y"])->getResult(),
            'ref_list' => $mReference->findBy([
                'sys_reference.name'              => 'SummaryProbation',
                'sys_reference.isactive'          => 'Y',
                'sys_ref_detail.isactive'         => 'Y',
            ], null, [
                'field'     => 'sys_ref_detail.name',
                'option'    => 'ASC'
            ])->getResult(),
        ];

        return $this->template->render('transaction/probation/evaluationprobation/v_evaluation_probation', $data);
    }

    public function showAll()
    {
        $mAccess = new M_AccessMenu($this->request);
        $mEmployee = new M_Employee($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelect();
            $join = $this->model->getJoin();
            $order = [
                '', // Hide column
                '', // Number column
                'trx_probation.documentno',
                'md_employee.fullname',
                'trx_probation.nik',
                'md_branch.name',
                'md_division.name',
                'md_position.name',
                'trx_probation.submissiondate',
                'trx_probation.registerdate',
                'trx_probation.probation_enddate',
                'trx_probation.docstatus',
                'sys_user.name'
            ];
            $search = [
                'trx_probation.documentno',
                'md_employee.fullname',
                'trx_probation.nik',
                'md_branch.name',
                'md_division.name',
                'md_position.name',
                'trx_probation.submissiondate',
                'trx_probation.registerdate',
                'trx_probation.probation_enddate',
                'trx_probation.docstatus',
                'sys_user.name'
            ];
            $sort = ['trx_probation.documentno' => 'ASC'];

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

                    $where['trx_probation.md_employee_id'] = [
                        'value'     => $arrMerge
                    ];
                } else if (!$roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $where['trx_probation.md_employee_id'] = [
                        'value'     => $arrEmployee
                    ];
                } else if ($roleEmp && empty($this->session->get('md_employee_id'))) {
                    $where['trx_probation.md_employee_id'] = [
                        'value'     => $arrEmpBased
                    ];
                } else {
                    $where['trx_probation.md_employee_id'] = $this->session->get('md_employee_id');
                }
            } else if (!empty($this->session->get('md_employee_id'))) {
                $where['trx_probation.md_employee_id'] = [
                    'value'     => $arrEmployee
                ];
            } else {
                $where['trx_probation.md_employee_id'] = $this->session->get('md_employee_id');
            }

            $where['trx_probation.submissiontype'] = $this->model->Pengajuan_Evaluasi_Probation;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_probation_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->documentno;
                $row[] = $value->employee_fullname;
                $row[] = $value->nik;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = $value->position;
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = format_dmy($value->registerdate, '-');
                $row[] = format_dmy($value->probation_enddate, '-');
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

            $post["submissiontype"] = $this->model->Pengajuan_Evaluasi_Probation;
            $post["necessary"] = 'EP';
            $post["category"] = 'evaluasi';

            try {
                if (!$this->validation->run($post, 'evaluasi_probation')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    // For checking if there exist monitoring probation same as this form
                    $refDoc1 = $this->model->where(["category" => $post['category'], 'md_employee_id' => $post['md_employee_id']])
                        ->whereIn("docstatus", ["CO", "IP"])
                        ->first();

                    // For checking monitoring 2, there's exist monitor 1 
                    $refDoc2 = $this->model->where(["category" => 'monitor 2', 'md_employee_id' => $post['md_employee_id']])
                        ->whereIn("docstatus", ["CO", "IP"])
                        ->first();

                    // For checking if employee status is probation
                    $employee = $mEmployee->where([$mEmployee->primaryKey => $post['md_employee_id'], 'isactive' => 'Y'])->first();

                    if ($employee->md_status_id != 100002) {
                        $response = message('success', false, 'karyawan saat ini tidak berstatus probation');
                    } else if ($refDoc1) {
                        $response = message('success', false, 'Tidak bisa mengajukan, karena sudah ada pengajuan lain');
                    } else if (!($refDoc2)) {
                        $response = message('success', false, 'Tidak bisa mengajukan, karena belum ada monitor kedua');
                    } else {
                        $this->entity->fill($post);

                        if ($this->isNew()) {
                            $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                            $docNo = $this->model->getInvNumber("submissiontype", $this->model->Pengajuan_Evaluasi_Probation, $post);
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
                $line = $this->modelDetail->where($this->model->primaryKey, $id)->findAll();
                $rowEmp = $mEmployee->where($mEmployee->primaryKey, $list[0]->getEmployeeId())->first();

                $list = $this->field->setDataSelect($mEmployee->table, $list, $mEmployee->primaryKey, $rowEmp->getEmployeeId(), $rowEmp->getValue());

                $title = $list[0]->getDocumentNo() . "_" . $rowEmp->getFullName();

                //* Need to set data into date field in form
                $list[0]->setRegisterDate(format_dmy($list[0]->registerdate, "-"));
                $list[0]->setSubmissionDate(format_dmy($list[0]->submissiondate, "-"));

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($title);
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setList($list);

                $result = [
                    'header'    => $this->field->store($fieldHeader),
                    'line'      => $this->tableLine($line)
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
            $menu = $this->request->uri->getSegment(2);

            try {
                if (!empty($_DocAction)) {
                    if ($_DocAction === $row->getDocStatus()) {
                        $response = message('error', true, 'Silahkan refresh terlebih dahulu');
                    } else if ($_DocAction === $this->DOCSTATUS_Completed) {
                        $refDoc = $this->model->where(["category" => $row->category, 'md_employee_id' => $row->md_employee_id])
                            ->whereIn("docstatus", ["CO", "IP"])
                            ->first();

                        if ($refDoc) {
                            $response = message('error', true, 'Sudah ada pengajuan lain');
                        } else {
                            $this->message = $cWfs->setScenario($this->entity, $this->model, $this->modelDetail, $_ID, $_DocAction, $menu, $this->session);
                            $response = message('success', true, true);
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


    public function tableLine($detail = [])
    {
        $table = [];

        foreach ($detail as $value) {
            $table[] = [
                'primarykey' => $value->trx_probation_detail_id,
                'isactive' => $value->isactive,
                'md_question_group_id' => $value->md_question_group_id,
                'no' => $value->no,
                'md_question_id' => $value->md_question_id,
                'answertype' => $value->answertype,
                'answer' => $value->answer,
                'description' => $value->description
            ];
        }

        return $table;
    }

    public function getBy($id)
    {
        if ($this->request->isAJAX()) {
            $response = [];

            try {
                $row = $this->model->find($id);
                $response['text'] = $row->documentno;
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}
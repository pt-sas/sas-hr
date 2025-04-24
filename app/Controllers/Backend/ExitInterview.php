<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use Config\Services;
use App\Models\M_Employee;
use App\Models\M_AccessMenu;
use App\Models\M_Branch;
use App\Models\M_Division;
use App\Models\M_EmployeeDeparture;
use App\Models\M_Interview;
use App\Models\M_InterviewDetail;
use App\Models\M_Position;
use App\Models\M_Question;
use App\Models\M_QuestionGroup;

class ExitInterview extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Interview($this->request);
        $this->modelDetail = new M_InterviewDetail($this->request);
        $this->entity = new \App\Entities\Interview();
    }

    public function index()
    {
        $mEmpDeparture = new M_EmployeeDeparture($this->request);
        $mAccess = new M_AccessMenu($this->request);
        $mEmployee = new M_Employee($this->request);
        $mQuestGroup = new M_QuestionGroup($this->request);
        $mQuestion = new M_Question($this->request);

        $roleEmp = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_All_Data');
        $arrAccess = $mAccess->getAccess($this->session->get("sys_user_id"));
        $arrEmployee = $mEmployee->getChartEmployee($this->session->get('md_employee_id'));
        $arrEmpStr = implode(" ,", $arrEmployee);

        $empSession = $this->session->get('md_employee_id');

        $where = "trx_employee_departure.docstatus = 'DR'";

        if ($arrAccess && isset($arrAccess["branch"]) && isset($arrAccess["division"])) {
            $arrBranch = $arrAccess["branch"];
            $arrDiv = $arrAccess["division"];

            $arrEmpBased = $mEmployee->getEmployeeBased($arrBranch, $arrDiv);
            $arrEmpBasedStr =
                implode(" ,", $arrEmpBased);

            if ($roleEmp && !empty($this->session->get('md_employee_id'))) {
                $arrMerge = array_unique(array_merge($arrEmpBased, $arrEmployee));
                $arrMergeStr = implode(" ,", $arrMerge);

                $where .= "trx_employee_departure.md_employee_id IN ($arrMergeStr)";
            } else if (!$roleEmp && !empty($this->session->get('md_employee_id'))) {
                $where .= " AND trx_employee_departure.md_employee_id IN ($arrEmpStr)";
            } else if ($roleEmp && empty($this->session->get('md_employee_id'))) {
                $where .= " AND trx_employee_departure.md_employee_id IN ($arrEmpBasedStr)";
            } else {
                $where .= " AND trx_employee_departure.md_employee_id = $empSession";
            }
        } else if (!empty($this->session->get('md_employee_id'))) {
            $where .= " AND trx_employee_departure.md_employee_id IN ($arrEmpStr)";
        } else {
            $where .= " AND trx_employee_departure.md_employee_id = $empSession";
        }

        $data = [
            'today'     => date('d-M-Y'),
            'resign_list' => $mEmpDeparture->findBy($where, null, [
                'field'     => 'trx_employee_departure.documentno',
                'option'    => 'ASC'
            ])->getResult(),
            'quest_group' => $mQuestGroup->where(["menu_url" => 'interview-keluar', "isactive" => "Y"])->findAll(),
            'question' => $mQuestion->getQuestion(["md_question_group.menu_url" => 'interview-keluar', "md_question_group.isactive" => "Y", "md_question.isactive" => "Y"])->getResult()
        ];

        return $this->template->render('transaction/exitinterview/v_exit_interview', $data);
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
                'trx_interview.documentno',
                'md_employee.fullname',
                'trx_interview.nik',
                'md_branch.name',
                'md_division.name',
                'trx_interview.submissiondate',
                'trx_interview.terminatedate',
                'trx_interview.description',
                'trx_interview.docstatus',
                'sys_user.name'
            ];
            $search = [
                'trx_interview.documentno',
                'md_employee.fullname',
                'trx_interview.nik',
                'md_branch.name',
                'md_division.name',
                'trx_interview.submissiondate',
                'trx_interview.terminatedate',
                'trx_interview.description',
                'trx_interview.docstatus',
                'sys_user.name'
            ];
            $sort = ['trx_interview.documentno' => 'ASC'];

            /**
             * Hak akses
             */
            $roleEmp = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_All_Data');
            $empDelegation = $mEmployee->getEmpDelegation($this->session->get('sys_user_id'));
            $arrAccess = $mAccess->getAccess($this->session->get("sys_user_id"));
            $arrEmployee = $mEmployee->getChartEmployee($this->session->get('md_employee_id'));

            if (!empty($empDelegation)) {
                $arrEmployee = array_unique(array_merge($arrEmployee, $empDelegation));
            }

            if ($arrAccess && isset($arrAccess["branch"]) && isset($arrAccess["division"])) {
                $arrBranch = $arrAccess["branch"];
                $arrDiv = $arrAccess["division"];

                $arrEmpBased = $mEmployee->getEmployeeBased($arrBranch, $arrDiv);

                if (!empty($empDelegation)) {
                    $arrEmpBased = array_unique(array_merge($arrEmpBased, $empDelegation));
                }

                if ($roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $arrMerge = array_unique(array_merge($arrEmpBased, $arrEmployee));

                    $where['md_employee.md_employee_id'] = [
                        'value'     => $arrMerge
                    ];
                } else if (!$roleEmp && !empty($this->session->get('md_employee_id'))) {
                    $where['md_employee.md_employee_id'] = [
                        'value'     => $arrEmployee
                    ];
                } else if ($roleEmp && empty($this->session->get('md_employee_id'))) {
                    $where['md_employee.md_employee_id'] = [
                        'value'     => $arrEmpBased
                    ];
                } else {
                    $where['md_employee.md_employee_id'] = $this->session->get('md_employee_id');
                }
            } else if (!empty($this->session->get('md_employee_id'))) {
                $where['md_employee.md_employee_id'] = [
                    'value'     => $arrEmployee
                ];
            } else {
                $where['md_employee.md_employee_id'] = $this->session->get('md_employee_id');
            }

            $where['trx_interview.submissiontype'] = $this->model->Pengajuan_Exit_interview;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_interview_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->documentno;
                $row[] = $value->ref_docno;
                $row[] = $value->employee_fullname;
                $row[] = $value->nik;
                $row[] = $value->branch;
                $row[] = $value->division;
                $row[] = $value->position;
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = format_dmy($value->terminatedate, '-');
                $row[] = $value->description;
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

            $post["submissiontype"] = $this->model->Pengajuan_Exit_Interview;
            $post["necessary"] = 'EI';

            $table = json_decode($post['table']);

            //! Mandatory property for detail validation
            $post['line'] = countLine($table);
            $post['detail'] = [
                'table' => arrTableLine($table)
            ];

            try {
                if (!$this->validation->run($post, 'exit_interview')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $this->entity->fill($post);

                    if ($this->isNew()) {
                        $this->entity->setDocStatus($this->DOCSTATUS_Drafted);

                        $docNo = $this->model->getInvNumber("submissiontype", $this->model->Pengajuan_Exit_Interview, $post);
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
        $mEmpDeparture = new M_EmployeeDeparture($this->request);
        // $mBranch = new M_Branch($this->request);
        // $mDivision = new M_Division($this->request);
        // $mPosition = new M_Position($this->request);

        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();
                $line = $this->modelDetail->where($this->model->primaryKey, $id)->findAll();
                $rowEmp = $mEmployee->where($mEmployee->primaryKey, $list[0]->getEmployeeId())->first();
                // $rowBranch = $mBranch->where($mBranch->primaryKey, $list[0]->getBranchId())->first();
                // $rowDivision = $mDivision->where($mDivision->primaryKey, $list[0]->getDivisionId())->first();
                // $rowPosition = $mPosition->where($mPosition->primaryKey, $list[0]->getPositionId())->first();
                $rowEmpDepart = $mEmpDeparture->where($mEmpDeparture->primaryKey, $list[0]->getReferenceId())->first();

                $list = $this->field->setDataSelect($mEmpDeparture->table, $list, "reference_id", $rowEmpDepart->getEmployeeDepartureId(), $rowEmpDepart->getDocumentNo() . ' - ' . $rowEmp->getValue());
                // $list = $this->field->setDataSelect($mBranch->table, $list, $mBranch->primaryKey, $rowBranch->getBranchId(), $rowBranch->getName());
                // $list = $this->field->setDataSelect($mDivision->table, $list, $mDivision->primaryKey, $rowDivision->getDivisionId(), $rowDivision->getName());
                // $list = $this->field->setDataSelect($mPosition->table, $list, $mPosition->primaryKey, $rowPosition->getPositionId(), $rowPosition->getName());

                $title = $list[0]->getDocumentNo() . "_" . $rowEmp->getFullName();

                //* Need to set data into date field in form
                $list[0]->setTerminateDate(format_dmy($list[0]->terminatedate, "-"));
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
                        $refDoc = $this->model->where(["reference_id" => $row->reference_id, 'md_employee_id' => $row->md_employee_id])
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
                'primarykey' => $value->trx_interview_detail_id,
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
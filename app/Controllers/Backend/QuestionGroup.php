<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_QuestionGroup;
use App\Models\M_Question;
use App\Models\M_Reference;
use App\Models\M_Menu;
use Config\Services;

class QuestionGroup extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_QuestionGroup($this->request);
        $this->modelDetail = new M_Question($this->request);
        $this->entity = new \App\Entities\QuestionGroup();
    }

    public function index()
    {
        $menu = new M_Menu($this->request);

        $data = [
            'menu'      => $menu->getMenuUrl()
        ];

        return $this->template->render('masterdata/question/v_question', $data);
    }

    public function showAll()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelect();
            $join = $this->model->getJoin();
            $order = $this->model->column_order;
            $sort = $this->model->order;
            $search = $this->model->column_search;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->md_question_group_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->value;
                $row[] = $value->name;
                $row[] = $value->description;
                $row[] = active($value->isactive);
                $row[] = $this->template->tableButton($ID);
                $data[] = $row;
            endforeach;

            $result = [
                'draw'              => $this->request->getPost('draw'),
                'recordsTotal'      => $this->datatable->countAll($table, $select, $order, $sort, $search),
                'recordsFiltered'   => $this->datatable->countFiltered($table, $select, $order, $sort, $search),
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

            try {
                $this->entity->fill($post);

                if (!$this->validation->run($post, 'list_pertanyaan')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
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
        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();
                $detail = $this->modelDetail->where($this->model->primaryKey, $id)->findAll();

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($list[0]->getName());
                $fieldHeader->setTable($this->model->table);
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

    public function getSeqCode()
    {
        if ($this->request->isAJAX()) {
            try {
                $number = $this->model->countAll();
                $number += 1;

                while (strlen($number) < 5) {
                    $number = "0" . $number;
                }

                $docno = "QG" . $number;

                $response = message('success', true, $docno);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function tableLine($set = null, $detail = [])
    {
        $reference = new M_Reference($this->request);

        $table = [];

        $fieldNo = new \App\Entities\Table();
        $fieldNo->setName("no");
        $fieldNo->setType("text");
        $fieldNo->setIsRequired(true);
        $fieldNo->setLength(50);

        $fieldQuestion = new \App\Entities\Table();
        $fieldQuestion->setName("question");
        $fieldQuestion->setType("text");
        $fieldQuestion->setIsRequired(true);
        $fieldQuestion->setLength(500);

        $fieldAnswerType = new \App\Entities\Table();
        $fieldAnswerType->setName("answertype");
        $fieldAnswerType->setType("select");
        $fieldAnswerType->setClass("select2");
        $fieldAnswerType->setLength(150);
        $fieldAnswerType->setIsRequired(true);
        $fieldAnswerType->setField([
            "id"    => "value",
            "text"  => "name"
        ]);

        $AnsTypeList = $reference->findBy([
            'sys_reference.name'              => 'AnswerType',
            'sys_reference.isactive'          => 'Y',
            'sys_ref_detail.isactive'         => 'Y',
        ], null, [
            'field'     => 'sys_ref_detail.name',
            'option'    => 'ASC'
        ])->getResult();

        $fieldAnswerType->setList($AnsTypeList);

        $fieldActive = new \App\Entities\Table();
        $fieldActive->setName("isactive");
        $fieldActive->setType("checkbox");
        $fieldActive->setClass("active");
        $fieldActive->setIsChecked(true);

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName("md_question_id");
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");

        //? Create
        if (empty($set)) {
            $table = [
                $this->field->fieldTable($fieldNo),
                $this->field->fieldTable($fieldQuestion),
                $this->field->fieldTable($fieldAnswerType),
                $this->field->fieldTable($fieldActive),
                $this->field->fieldTable($btnDelete)
            ];
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $fieldNo->setValue($row->getNo());
                $fieldQuestion->setValue($row->getQuestion());
                $fieldAnswerType->setValue($row->getAnswerType());
                $fieldActive->setValue($row->getIsActive());
                $btnDelete->setValue($row->getQuestionId());

                if ($row->getIsActive() === "N") {
                    $fieldNo->setIsReadonly(true);
                    $fieldQuestion->setIsReadonly(true);
                    $fieldAnswerType->setIsReadonly(true);
                    $fieldActive->setIsChecked(false);
                } else {
                    $fieldNo->setIsReadonly(false);
                    $fieldQuestion->setIsReadonly(false);
                    $fieldAnswerType->setIsReadonly(false);
                    $fieldActive->setIsChecked(true);
                }

                $table[] = [
                    $this->field->fieldTable($fieldNo),
                    $this->field->fieldTable($fieldQuestion),
                    $this->field->fieldTable($fieldAnswerType),
                    $this->field->fieldTable($fieldActive),
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        return json_encode($table);
    }

    // public function getList()
    // {
    //     if ($this->request->isAjax()) {
    //         $post = $this->request->getVar();

    //         $response = [];

    //         try {
    //             if (isset($post['search'])) {
    //                 $list = $this->modelDetail->where('isactive', 'Y')
    //                     ->like('name', $post['search'])
    //                     ->orderBy('name', 'ASC')
    //                     ->findAll();
    //             } else if (!empty($post['name'])) {
    //                 $first = $this->model->where('isactive', 'Y')
    //                     ->like('name', $post['name'])->first();

    //                 $list = $this->modelDetail->where([
    //                     'isactive'  => 'Y',
    //                     $this->model->primaryKey => $first->getReferenceId()
    //                 ])->orderBy('name', 'ASC')
    //                     ->findAll();
    //             } else if (!empty($post['criteria'])) {
    //                 $first = $this->model->where('isactive', 'Y')
    //                     ->like('description', $post['criteria'])->first();

    //                 $list = $this->modelDetail->where([
    //                     'isactive'  => 'Y',
    //                     $this->model->primaryKey => $first->getReferenceId()
    //                 ])->orderBy('name', 'ASC')
    //                     ->findAll();
    //             } else {
    //                 $list = $this->modelDetail->where('isactive', 'Y')
    //                     ->orderBy('name', 'ASC')
    //                     ->findAll();
    //             }

    //             foreach ($list as $key => $row) :
    //                 $response[$key]['id'] = $row->getValue();
    //                 $response[$key]['text'] = $row->getName();
    //             endforeach;
    //         } catch (\Exception $e) {
    //             $response = message('error', false, $e->getMessage());
    //         }

    //         return $this->response->setJSON($response);
    //     }
    // }
}
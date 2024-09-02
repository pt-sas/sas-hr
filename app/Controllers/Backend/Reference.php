<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Reference;
use App\Models\M_ReferenceDetail;
use Config\Services;

class Reference extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Reference($this->request);
        $this->modelDetail = new M_ReferenceDetail($this->request);
        $this->entity = new \App\Entities\Reference();
    }

    public function index()
    {
        $data = [
            'ref_list' => $this->model->findBy([
                'sys_reference.name'              => 'SYS_Reference Validation Types',
                'sys_reference.isactive'          => 'Y',
                'sys_ref_detail.isactive'         => 'Y',
            ], null, [
                'field'     => 'sys_ref_detail.name',
                'option'    => 'ASC'
            ])->getResult()
        ];

        return $this->template->render('backend/configuration/reference/v_reference', $data);
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
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->sys_reference_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->name;
                $row[] = $value->ref_detail;
                $row[] = $value->description;
                $row[] = active($value->isactive);
                $row[] = $this->template->tableButton($ID);
                $data[] = $row;
            endforeach;

            $result = [
                'draw'              => $this->request->getPost('draw'),
                'recordsTotal'      => $this->datatable->countAll($table, $select, $order, $sort, $search),
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

            try {
                $this->entity->fill($post);

                if (!$this->validation->run($post, 'reference')) {
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

    public function tableLine($set = null, $detail = [])
    {
        $table = [];

        $fieldValue = new \App\Entities\Table();
        $fieldValue->setName("value");
        $fieldValue->setType("text");
        $fieldValue->setIsRequired(true);
        $fieldValue->setLength(150);

        $fieldName = new \App\Entities\Table();
        $fieldName->setName("name");
        $fieldName->setType("text");
        $fieldName->setIsRequired(true);
        $fieldName->setLength(200);

        $fieldDesc = new \App\Entities\Table();
        $fieldDesc->setName("description");
        $fieldDesc->setType("text");
        $fieldDesc->setLength(250);

        $fieldActive = new \App\Entities\Table();
        $fieldActive->setName("isactive");
        $fieldActive->setType("checkbox");
        $fieldActive->setClass("active");
        $fieldActive->setIsChecked(true);

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName("sys_ref_detail_id");
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");

        //? Create
        if (empty($set)) {
            $table = [
                $this->field->fieldTable($fieldValue),
                $this->field->fieldTable($fieldName),
                $this->field->fieldTable($fieldDesc),
                $this->field->fieldTable($fieldActive),
                $this->field->fieldTable($btnDelete)
            ];
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $fieldValue->setValue($row->getValue());
                $fieldName->setValue($row->getName());
                $fieldDesc->setValue($row->getDescription());
                $fieldActive->setValue($row->getIsActive());
                $btnDelete->setValue($row->getRefDetailId());

                if ($row->getIsActive() === "N") {
                    $fieldValue->setIsReadonly(true);
                    $fieldName->setIsReadonly(true);
                    $fieldDesc->setIsReadonly(true);
                    $fieldActive->setIsChecked(false);
                } else {
                    $fieldValue->setIsReadonly(false);
                    $fieldName->setIsReadonly(false);
                    $fieldDesc->setIsReadonly(false);
                    $fieldActive->setIsChecked(true);
                }

                $table[] = [
                    $this->field->fieldTable($fieldValue),
                    $this->field->fieldTable($fieldName),
                    $this->field->fieldTable($fieldDesc),
                    $this->field->fieldTable($fieldActive),
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        return json_encode($table);
    }

    public function getList()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->getVar();

            $response = [];

            try {
                if (isset($post['search'])) {
                    $list = $this->modelDetail->where('isactive', 'Y')
                        ->like('name', $post['search'])
                        ->orderBy('name', 'ASC')
                        ->findAll();
                } else if (!empty($post['name'])) {
                    $first = $this->model->where('isactive', 'Y')
                        ->like('name', $post['name'])->first();

                    $list = $this->modelDetail->where([
                        'isactive'  => 'Y',
                        $this->model->primaryKey => $first->getReferenceId()
                    ])->orderBy('name', 'ASC')
                        ->findAll();
                } else if (!empty($post['criteria'])) {
                    $first = $this->model->where('isactive', 'Y')
                        ->like('description', $post['criteria'])->first();

                    $list = $this->modelDetail->where([
                        'isactive'  => 'Y',
                        $this->model->primaryKey => $first->getReferenceId()
                    ])->orderBy('name', 'ASC')
                        ->findAll();
                } else {
                    $list = $this->modelDetail->where('isactive', 'Y')
                        ->orderBy('name', 'ASC')
                        ->findAll();
                }

                foreach ($list as $key => $row) :
                    $response[$key]['id'] = $row->getValue();
                    $response[$key]['text'] = $row->getName();
                endforeach;
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}
<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Rule;
use App\Models\M_Menu;
use App\Models\M_Reference;
use App\Models\M_ReferenceDetail;
use Config\Services;
use TCPDF;

class Rule extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Rule($this->request);
        $this->entity = new \App\Entities\Rule();
    }

    public function index()
    {
        $menu = new M_Menu($this->request);

        $data = [
            'menu'      => $menu->getMenuUrl()
        ];

        return $this->template->render('masterdata/rule/v_rule', $data);
    }

    public function showAll()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->findAll();
            $order = $this->model->column_order;
            $sort = $this->model->order;
            $search = $this->model->column_search;

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->md_rule_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->name;
                $row[] = $value->condition;
                $row[] = $value->value;
                $row[] = $value->menu_url;
                $row[] = $value->priority;
                $row[] = active($value->isdetail);
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

            try {
                $this->entity->fill($post);

                if (!$this->validation->run($post, 'rule')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $response = $this->save();

                    if (isset($response[0]["success"])) {
                        $id = $this->getID();

                        if ($this->isNew()) {
                            $id = $this->insertID;
                            $response[0]["foreignkey"] = $id;
                        }
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
        $mRefDetail = new M_ReferenceDetail($this->request);
        $mReference = new M_Reference($this->request);

        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();

                if (!empty($list[0]->getIsDetail())) {
                    $rowIsDetail = $mReference->findBy([
                        'sys_reference.name'              => 'StatusActive',
                        'sys_reference.isactive'          => 'Y',
                        'sys_ref_detail.isactive'         => 'Y',
                        'sys_ref_detail.value'            => $list[0]->getIsDetail(),
                    ])->getRow();
                    $list = $this->field->setDataSelect($mRefDetail->table, $list, "isdetail", $rowIsDetail->value, $rowIsDetail->name);
                }

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($list[0]->getName());
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

    public function getBy($id)
    {
        if ($this->request->isAJAX()) {
            $response = [];

            try {
                $row = $this->model->find($id);
                $response['text'] = $row->getName();
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}

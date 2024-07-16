<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_DocumentType;
use Config\Services;

class DocumentType extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_DocumentType($this->request);
        $this->entity = new \App\Entities\DocumentType();
    }

    public function index()
    {
        return $this->template->render('masterdata/documenttype/v_doctype');
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
                $ID = $value->md_doctype_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
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

            try {
                $this->entity->fill($post);

                if (!$this->validation->run($post, 'doctype')) {
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

                $title = $list[0]->getName();

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($title);
                $fieldHeader->setTable($this->model->table);
                $fieldHeader->setList($list);

                $result = [
                    'header'   => $this->field->store($fieldHeader)
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

    public function getList()
    {
        if ($this->request->isAjax()) {
            $post = $this->request->getVar();

            $response = [];

            try {
                if (isset($post['search'])) {
                    if (isset($post['isinternal']) && $post['isinternal'] === "Y") {
                        $list = $this->model->where('isactive', 'Y')
                            ->whereIn('md_doctype_id', [100010, 100011, 100012, 100013])
                            ->like('name', $post['search'])
                            ->orderBy('name', 'ASC')
                            ->findAll();
                    } else {
                        $list = $this->model->where('isactive', 'Y')
                            ->whereNotIn('md_doctype_id', [100010, 100011, 100012, 100013])
                            ->like('name', $post['search'])
                            ->orderBy('name', 'ASC')
                            ->findAll();
                    }
                } else {
                    if (isset($post['isinternal']) && $post['isinternal'] === "Y") {
                        $list = $this->model->where('isactive', 'Y')
                            ->whereIn('md_doctype_id', [100010, 100011, 100012, 100013])
                            ->orderBy('name', 'ASC')
                            ->findAll();
                    } else {
                        $list = $this->model->where('isactive', 'Y')
                            ->whereNotIn('md_doctype_id', [100010, 100011, 100012, 100013])
                            ->orderBy('name', 'ASC')
                            ->findAll();
                    }
                }

                foreach ($list as $key => $row) :
                    $response[$key]['id'] = $row->getDocTypeId();
                    $response[$key]['text'] = $row->getName();
                endforeach;
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}

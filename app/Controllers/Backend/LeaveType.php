<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_LeaveType;
use App\Models\M_Reference;
use Config\Services;

class LeaveType extends BaseController
{
    public function __construct()
    {

        $this->request = Services::request();
        $this->model = new M_LeaveType($this->request);
        $this->entity = new \App\Entities\LeaveType();
    }

    public function index()
    {
        $reference = new M_Reference($this->request);
        $data = [
            'ref_list' => $reference->findBy([
                'sys_reference.name'              => 'Gender',
                'sys_reference.isactive'          => 'Y',
                'sys_ref_detail.isactive'         => 'Y',
            ], null, [
                'field'     => 'sys_ref_detail.name',
                'option'    => 'ASC'
            ])->getResult()
        ];
        return $this->template->render('masterdata/leavetype/v_leavetype', $data);
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
                $ID = $value->md_leavetype_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->value;
                $row[] = $value->name;
                $row[] = gender($value->gender);
                $row[] = $value->duration;
                $row[] = durationtype($value->duration_type);
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

                if (!$this->validation->run($post, 'leavetype')) {
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

                $title = 'Tipe Cuti';

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

    public function getSeqCode()
    {
        if ($this->request->isAJAX()) {
            try {
                $number = $this->model->countAll();
                $number += 1;

                while (strlen($number) < 5) {
                    $number = "0" . $number;
                }

                $docno = "LT" . $number;

                $response = message('success', true, $docno);
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
                    $list = $this->model->where('isactive', 'Y')
                        ->like('name', $post['search'])
                        ->orderBy('name', 'ASC')
                        ->findAll();
                } else {
                    $list = $this->model->where('isactive', 'Y')
                        ->orderBy('name', 'ASC')
                        ->findAll();
                }

                foreach ($list as $key => $row) :
                    $response[$key]['id'] = $row->getLeaveTypeId();
                    $response[$key]['text'] = $row->getName();

                endforeach;
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}

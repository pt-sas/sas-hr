<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_AttendanceMachine;
use App\Models\M_Branch;
use App\Models\M_Province;
use App\Models\M_City;
use Config\Services;

class AttendanceMachine extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_AttendanceMachine($this->request);
        $this->entity = new \App\Entities\City();
    }

    public function index()
    {
        return $this->template->render('backend/configuration/attendancemachine/v_attendance_machine');
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
                $ID = $value->md_attendance_machines_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->name;
                $row[] = $value->serialnumber;
                $row[] = $value->branch;
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

            try {
                $this->entity->fill($post);

                if (!$this->validation->run($post, 'attendance_machine')) {
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
        $mBranch = new M_Branch($this->request);

        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();

                if (!empty($list[0]->md_branch_id)) {
                    $rowBranch = $mBranch->find($list[0]->md_branch_id);

                    $list = $this->field->setDataSelect($mBranch->table, $list, 'md_branch_id', $rowBranch->getBranchId(), $rowBranch->getName());
                }

                $title = 'Mesin Absen';

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
}

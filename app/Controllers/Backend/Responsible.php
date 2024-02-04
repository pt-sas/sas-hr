<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Responsible;
use App\Models\M_Role;
use App\Models\M_User;
use App\Models\M_AlertRecipient;
use App\Models\M_Reference;
use App\Models\M_ReferenceDetail;
use Config\Services;

class Responsible extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Responsible($this->request);
        $this->entity = new \App\Entities\Responsible();
    }

    public function index()
    {
        $user = new M_User($this->request);

        $data = [
            'user'        => $user->where('isactive', 'Y')
                ->orderBy('name', 'ASC')
                ->findAll()
        ];

        return $this->template->render('backend/configuration/responsible/v_responsible', $data);
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
                $ID = $value->sys_wfresponsible_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->name;
                $row[] = $value->description;
                $row[] = $value->res_type;
                $row[] = $value->role;
                $row[] = $value->user;
                $row[] = active($value->isactive);
                $row[] = $this->template->tableButton($ID);
                $data[] = $row;
            endforeach;

            $result = [
                'draw'              => $this->request->getPost('draw'),
                'recordsTotal'      => $this->datatable->countAll($table, $select, $order, $sort, $search, $join),
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

                if ($post['responsibletype'] === 'R')
                    $this->entity->setUserId(0);

                if ($post['responsibletype'] === 'H')
                    $this->entity->setRoleId(0);

                if (!$this->validation->run($post, 'responsible')) {
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
        $mAlert = new M_AlertRecipient($this->request);
        $mRole = new M_Role($this->request);
        $mUser = new M_User($this->request);
        $mRefDetail = new M_ReferenceDetail($this->request);
        $mReference = new M_Reference($this->request);

        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();

                if (!empty($list[0]->getResponsibleType())) {
                    $rowType = $mReference->findBy([
                        'sys_reference.name'              => 'WF_ParticipantType',
                        'sys_reference.isactive'          => 'Y',
                        'sys_ref_detail.isactive'         => 'Y',
                        'sys_ref_detail.value'            => $list[0]->getResponsibleType(),
                    ])->getRow();

                    $list = $this->field->setDataSelect($mRefDetail->table, $list, "responsibletype", $rowType->value, $rowType->name);
                }

                if (!empty($list[0]->getRoleId())) {
                    $rowRole = $mRole->find($list[0]->getRoleId());
                    $list = $this->field->setDataSelect($mRole->table, $list, $mRole->primaryKey, $rowRole->getRoleId(), $rowRole->getName());
                }

                if (!empty($list[0]->getUserId())) {
                    $rowUser = $mUser->find($list[0]->getUserId());
                    $list = $this->field->setDataSelect($mUser->table, $list, $mUser->primaryKey, $rowUser->getUserId(), $rowUser->getName());
                }

                $fieldHeader = new \App\Entities\Table();
                $fieldHeader->setTitle($list[0]->getName());
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
                $result = $this->model->delete($id);
                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}

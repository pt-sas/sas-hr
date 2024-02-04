<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_NotificationText;
use App\Models\M_Reference;
use App\Models\M_ReferenceDetail;
use Config\Services;

class NotificationText extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_NotificationText($this->request);
        $this->entity = new \App\Entities\NotificationText();
    }

    public function index()
    {
        return $this->template->render('backend/configuration/notification/v_notiftext');
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
                $ID = $value->sys_notiftext_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->name;
                $row[] = $value->subject;
                $row[] = $value->text;
                $row[] = $value->notif_type;
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

                if (!$this->validation->run($post, 'notifText')) {
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
        $mRefDetail = new M_ReferenceDetail($this->request);
        $mReference = new M_Reference($this->request);

        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();

                if (!empty($list[0]->getNotifType())) {
                    $rowType = $mReference->findBy([
                        'sys_reference.name'              => 'SYS_NotificationType',
                        'sys_reference.isactive'          => 'Y',
                        'sys_ref_detail.isactive'         => 'Y',
                        'sys_ref_detail.value'            => $list[0]->getNotifType(),
                    ])->getRow();

                    $list = $this->field->setDataSelect($mRefDetail->table, $list, "notiftype", $rowType->value, $rowType->name);
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
                $result = $this->delete($id);
                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}

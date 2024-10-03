<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Benefit;
use App\Models\M_Branch;
use App\Models\M_Division;
use App\Models\M_Position;
use App\Models\M_Levelling;
use App\Models\M_Status;
use Config\Services;

class Benefit extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Benefit($this->request);
        $this->entity = new \App\Entities\Benefit();
    }

    public function index()
    {
        return $this->template->render('masterdata/benefit/v_benefit');
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
                $ID = $value->md_benefit_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->name;
                $row[] = $value->cabang;
                $row[] = $value->divisi;
                $row[] = $value->level;
                $row[] = $value->jabatan;
                $row[] = $value->status;
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
                if (!$this->validation->run($post, 'benefit')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $this->entity->fill($post);

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
        $mPosition = new M_Position($this->request);
        $mLeveling = new M_Levelling($this->request);
        $mBranch = new M_Branch($this->request);
        $mDiv = new M_Division($this->request);
        $mStatus = new M_Status($this->request);

        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();

                if (!empty($list[0]->getBranchId())) {
                    $rowBranch = $mBranch->find($list[0]->getBranchId());
                    $list = $this->field->setDataSelect($mBranch->table, $list, $mBranch->primaryKey, $rowBranch->getBranchId(), $rowBranch->getName());
                }

                if (!empty($list[0]->getDivisionId())) {
                    $rowDiv = $mDiv->find($list[0]->getDivisionId());
                    $list = $this->field->setDataSelect($mDiv->table, $list, $mDiv->primaryKey, $rowDiv->getDivisionId(), $rowDiv->getName());
                }

                if (!empty($list[0]->getPositionId())) {
                    $rowPosition = $mPosition->find($list[0]->getPositionId());
                    $list = $this->field->setDataSelect($mPosition->table, $list, $mPosition->primaryKey, $rowPosition->getPositionId(), $rowPosition->getName());
                }

                if (!empty($list[0]->getLevellingId())) {
                    $rowLevel = $mLeveling->find($list[0]->getLevellingId());
                    $list = $this->field->setDataSelect($mLeveling->table, $list, $mLeveling->primaryKey, $rowLevel->getLevellingId(), $rowLevel->getName());
                }

                if (!empty($list[0]->getStatusId())) {
                    $rowStatus = $mStatus->find($list[0]->getStatusId());
                    $list = $this->field->setDataSelect($mStatus->table, $list, $mStatus->primaryKey, $rowStatus->getStatusId(), $rowStatus->getName());
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

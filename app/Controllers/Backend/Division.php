<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Division;
use App\Models\M_Branch;
use App\Models\M_AccessMenu;
use App\Models\M_EmpDivision;
use Config\Services;

class Division extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Division($this->request);
        $this->entity = new \App\Entities\Division();
    }

    public function index()
    {
        return $this->template->render('masterdata/division/v_division');
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
                $ID = $value->md_division_id;

                $number++;

                $row[] = $ID;
                $row[] = $number;
                $row[] = $value->value;
                $row[] = $value->name;
                $row[] = $value->description;
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

                if (!$this->validation->run($post, 'division')) {
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
        $branch = new M_Branch($this->request);

        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();

                if (!empty($list[0]->getBranchId())) {
                    $rowEmp = $branch->find($list[0]->getBranchId());

                    $list = $this->field->setDataSelect($branch->table, $list, 'md_branch_id', $rowEmp->getBranchId(), $rowEmp->getName());
                }
                $title = 'Divisi';

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

                $docno = "DV" . $number;

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
            $mAccess = new M_AccessMenu($this->request);
            $mEmpDivision = new M_EmpDivision($this->request);
            $post = $this->request->getVar();

            $response = [];

            try {
                $userID = $this->session->get('sys_user_id');
                $employeeID = $this->session->get('md_employee_id');

                if (isset($post['name']) && $post['name'] == "Access") {
                    $arrAccess = $mAccess->getAccess($userID);
                    $divisionList = !empty($employeeID) ? array_column($mEmpDivision->where('md_employee_id', $employeeID)->findAll(), 'md_division_id') : [];

                    if ($arrAccess && isset($arrAccess["division"])) {
                        $divisionList = array_unique(array_merge($divisionList, $arrAccess['division']));
                    }
                }

                if (isset($post['search'])) {
                    if (isset($post['name']) && $post['name'] == "Access") {
                        $list = $this->model->where('isactive', 'Y')
                            ->whereIn('md_division_id', !empty($divisionList) ? $divisionList : [0])
                            ->like('name', $post['search'])
                            ->orderBy('name', 'ASC')
                            ->findAll();
                    } else {
                        $list = $this->model->where('isactive', 'Y')
                            ->like('name', $post['search'])
                            ->orderBy('name', 'ASC')
                            ->findAll();
                    }
                } else if (isset($post['name']) && $post['name'] == "Access") {
                    $list = $this->model->where('isactive', 'Y')
                        ->whereIn('md_division_id', !empty($divisionList) ? $divisionList : [0])
                        ->orderBy('name', 'ASC')
                        ->findAll();
                } else if (isset($post[$this->model->primaryKey])) {
                    $id = explode(",", $post[$this->model->primaryKey]);

                    $list = $this->model->where('isactive', 'Y')
                        ->whereIn($this->model->primaryKey, $id)
                        ->orderBy('name', 'ASC')
                        ->findAll();
                } else {
                    $list = $this->model->where('isactive', 'Y')
                        ->orderBy('name', 'ASC')
                        ->findAll();
                }

                // if (!empty($post['reference']))
                //     $value = $employee->find($post['reference']);

                foreach ($list as $key => $row) :
                    $response[$key]['id'] = $row->getDivisionId();
                    $response[$key]['text'] = $row->getName();

                // if (!empty($post['reference']))
                //     $response[$key]['key'] = $value->getDivisionId();
                endforeach;
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}

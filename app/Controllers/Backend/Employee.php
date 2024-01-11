<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Branch;
use App\Models\M_Division;
use App\Models\M_Employee;
use App\Models\M_EmpBranch;
use App\Models\M_EmpDivision;
use Config\Services;

class Employee extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Employee($this->request);
        $this->entity = new \App\Entities\Employee();
    }

    public function getDetailEmployee()
    {
        if ($this->request->isAJAX()) {
            $post = $this->request->getVar();
            $response = [];

            try {
                $mEmpBranch = new M_EmpBranch($this->request);
                $mEmpDiv = new M_EmpDivision($this->request);
                $mBranch = new M_Branch($this->request);
                $mDiv = new M_Division($this->request);

                $id = $post["md_employee_id"];
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();
                $rowBranch = $mEmpBranch->where($this->model->primaryKey, $id)->findAll();
                $rowDiv = $mEmpDiv->where($this->model->primaryKey, $id)->findAll();

                $list = $this->field->setDataSelect($mEmpBranch->table, $list, $mBranch->primaryKey, $mBranch->primaryKey, "name", $rowBranch);
                $list = $this->field->setDataSelect($mEmpDiv->table, $list, $mDiv->primaryKey, $mDiv->primaryKey, "name", $rowDiv);

                $response = $list;
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
                        ->like('value', $post['search'])
                        ->orderBy('value', 'ASC')
                        ->findAll();
                } else {
                    $list = $this->model->where('isactive', 'Y')
                        ->orderBy('value', 'ASC')
                        ->findAll();
                }

                foreach ($list as $key => $row) :
                    $response[$key]['id'] = $row->getEmployeeId();
                    $response[$key]['text'] = $row->getValue();
                endforeach;
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}

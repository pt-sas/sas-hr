<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use Config\Services;
use App\Models\M_Employee;
use App\Models\M_EmpJob;

class EmpJob extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Employee($this->request);
        $this->modelDetail = new M_EmpJob($this->request);
        $this->entity = new \App\Entities\Employee();
    }

    public function create()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            $table = json_decode($post['table']);

            // //! Mandatory property for detail validation
            $post['line'] = countLine($table);
            $post['detail'] = [
                'table' => arrTableLine($table)
            ];

            try {
                if ($this->isNew())
                    $this->entity->setEmployeeId($post["md_employee_id"]);

                if (!$this->validation->run($post, 'employee_job')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $response = $this->save();

                    if (isset($response[0]["success"])) {
                        if (!isset($post["id"]))
                            $response = message('success', true, notification("insert"));

                        $detail = $this->modelDetail->where($this->model->primaryKey, $post["md_employee_id"])->findAll();
                        $response[0]["line"] = $this->tableLine('edit', $detail);
                    }
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function show($id = null)
    {
        if ($this->request->isAJAX()) {
            $get = $this->request->getGet();

            $result = [];

            try {
                $list = $this->model->where($this->model->primaryKey, $id)->findAll();
                $detail = $this->modelDetail->where($this->model->primaryKey, $id)->findAll();

                if (isset($get["md_employee_id"])) {
                    $list = $this->model->where($this->model->primaryKey, $get["md_employee_id"])->findAll();
                    $detail = $this->modelDetail->where($this->model->primaryKey, $get["md_employee_id"])->findAll();
                }

                $fieldHeader = new \App\Entities\Table();
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

    public function tableLine($set = null, $detail = [])
    {
        $roleEmpAdm = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_Admin');

        $table = [];
        $id = 0;

        $fieldCompany = new \App\Entities\Table();
        $fieldCompany->setName("company");
        $fieldCompany->setType("text");
        $fieldCompany->setIsRequired(true);
        $fieldCompany->setLength(250);

        $fieldStartDate = new \App\Entities\Table();
        $fieldStartDate->setName("startdate");
        $fieldStartDate->setType("text");
        $fieldStartDate->setClass("datepicker");
        $fieldStartDate->setIsRequired(true);
        $fieldStartDate->setLength(130);

        $fieldEndDate = new \App\Entities\Table();
        $fieldEndDate->setName("enddate");
        $fieldEndDate->setType("text");
        $fieldEndDate->setClass("datepicker");
        $fieldEndDate->setIsRequired(true);
        $fieldEndDate->setLength(130);

        $fieldPosition = new \App\Entities\Table();
        $fieldPosition->setName("position");
        $fieldPosition->setType("text");
        $fieldPosition->setIsRequired(true);
        $fieldPosition->setLength(250);

        $fieldReason = new \App\Entities\Table();
        $fieldReason->setName("reason");
        $fieldReason->setType("text");
        $fieldReason->setLength(250);

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName($this->modelDetail->primaryKey);
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");

        // TODO : Set ReadOnly if no role Emp Admin
        if (!$roleEmpAdm) {
            $fieldCompany->setIsReadonly(true);
            $fieldStartDate->setIsReadonly(true);
            $fieldEndDate->setIsReadonly(true);
            $fieldPosition->setIsReadonly(true);
            $fieldReason->setIsReadonly(true);
            $btnDelete->setIsReadonly(true);
        }

        //? Create
        if (empty($set)) {
            $table = [
                $id,
                $this->field->fieldTable($fieldCompany),
                $this->field->fieldTable($fieldStartDate),
                $this->field->fieldTable($fieldEndDate),
                $this->field->fieldTable($fieldPosition),
                $this->field->fieldTable($fieldReason),
                $this->field->fieldTable($btnDelete)
            ];
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $id = $row->getEmpJobId();
                $startDate = $row->getStartDate() ? format_dmy($row->getStartDate(), "-") : null;
                $endDate = $row->getEndDate() ? format_dmy($row->getEndDate(), "-") : null;

                $fieldCompany->setValue($row->getCompany());
                $fieldStartDate->setValue($startDate);
                $fieldEndDate->setValue($endDate);
                $fieldPosition->setValue($row->getPosition());
                $fieldReason->setValue($row->getReason());
                $btnDelete->setValue($id);

                $table[] = [
                    $id,
                    $this->field->fieldTable($fieldCompany),
                    $this->field->fieldTable($fieldStartDate),
                    $this->field->fieldTable($fieldEndDate),
                    $this->field->fieldTable($fieldPosition),
                    $this->field->fieldTable($fieldReason),
                    $roleEmpAdm ? $this->field->fieldTable($btnDelete) : ''
                ];
            endforeach;
        }

        return json_encode($table);
    }
}
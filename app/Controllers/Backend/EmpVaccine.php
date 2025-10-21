<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use Config\Services;
use App\Models\M_Employee;
use App\Models\M_EmpVaccine;
use App\Models\M_Reference;

class EmpVaccine extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Employee($this->request);
        $this->modelDetail = new M_EmpVaccine($this->request);
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

                if (!$this->validation->run($post, 'employee_vaccine')) {
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
        $reference = new M_Reference($this->request);
        $roleEmpAdm = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_Admin');

        $table = [];
        $id = 0;

        $fieldVaccineType = new \App\Entities\Table();
        $fieldVaccineType->setName("vaccinetype");
        $fieldVaccineType->setType("select");
        $fieldVaccineType->setClass("select2");
        $fieldVaccineType->setIsRequired(true);
        $fieldVaccineType->setLength(200);
        $fieldVaccineType->setField([
            "id"    => "value",
            "text"  => "name"
        ]);

        $vaccineList = $reference->findBy([
            'sys_reference.name'              => 'Vaccine',
            'sys_reference.isactive'          => 'Y',
            'sys_ref_detail.isactive'         => 'Y',
        ], null, [
            'field'     => 'sys_ref_detail.name',
            'option'    => 'ASC'
        ])->getResult();

        $fieldVaccineType->setList($vaccineList);

        $fieldVaccineDate = new \App\Entities\Table();
        $fieldVaccineDate->setName("vaccinedate");
        $fieldVaccineDate->setType("text");
        $fieldVaccineDate->setClass("datepicker");
        $fieldVaccineDate->setIsRequired(true);
        $fieldVaccineDate->setLength(130);

        $fieldDesc = new \App\Entities\Table();
        $fieldDesc->setName("description");
        $fieldDesc->setType("text");
        $fieldDesc->setLength(250);

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName($this->modelDetail->primaryKey);
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");

        // TODO : Set ReadOnly if no role Emp Admin
        if (!$roleEmpAdm) {
            $fieldVaccineType->setIsReadonly(true);
            $fieldVaccineDate->setIsReadonly(true);
            $fieldDesc->setIsReadonly(true);
            $btnDelete->setIsReadonly(true);
        }

        //? Create
        if (empty($set)) {
            $table = [
                $id,
                $this->field->fieldTable($fieldVaccineType),
                $this->field->fieldTable($fieldVaccineDate),
                $this->field->fieldTable($fieldDesc),
                $this->field->fieldTable($btnDelete)
            ];
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $id = $row->getEmpVaccineId();
                $vaccineDate = $row->getVaccineDate() ? format_dmy($row->getVaccineDate(), "-") : null;

                $fieldVaccineType->setValue($row->getVaccineType());
                $fieldVaccineDate->setValue($vaccineDate);
                $fieldDesc->setValue($row->getDescription());
                $btnDelete->setValue($id);

                $table[] = [
                    $id,
                    $this->field->fieldTable($fieldVaccineType),
                    $this->field->fieldTable($fieldVaccineDate),
                    $this->field->fieldTable($fieldDesc),
                    $roleEmpAdm ? $this->field->fieldTable($btnDelete) : ''
                ];
            endforeach;
        }

        return json_encode($table);
    }
}
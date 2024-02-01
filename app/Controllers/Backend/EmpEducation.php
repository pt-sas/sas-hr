<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use Config\Services;
use App\Models\M_Employee;
use App\Models\M_EmpEducation;
use App\Models\M_Reference;

class EmpEducation extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Employee($this->request);
        $this->modelDetail = new M_EmpEducation($this->request);
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

                if (!$this->validation->run($post, 'employee_education')) {
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

                if ($detail) {
                    $fieldHeader = new \App\Entities\Table();
                    $fieldHeader->setTable($this->model->table);
                    $fieldHeader->setList($list);

                    $result = [
                        'header'    => $this->field->store($fieldHeader),
                        'line'      => $this->tableLine('edit', $detail)
                    ];
                }

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

        $table = [];
        $id = 0;

        $fieldEducation = new \App\Entities\Table();
        $fieldEducation->setName("education");
        $fieldEducation->setType("select");
        $fieldEducation->setClass("select2");
        $fieldEducation->setIsRequired(true);
        $fieldEducation->setLength(100);
        $fieldEducation->setField([
            "id"    => "value",
            "text"  => "name"
        ]);

        $educList = $reference->findBy([
            'sys_reference.name'              => 'Education',
            'sys_reference.isactive'          => 'Y',
            'sys_ref_detail.isactive'         => 'Y',
        ], null, [
            'field'     => 'sys_ref_detail.sys_ref_detail_id',
            'option'    => 'ASC'
        ])->getResult();

        $fieldEducation->setList($educList);

        $fieldSchool = new \App\Entities\Table();
        $fieldSchool->setName("school");
        $fieldSchool->setType("text");
        $fieldSchool->setIsRequired(true);
        $fieldSchool->setLength(300);

        $fieldCity = new \App\Entities\Table();
        $fieldCity->setName("city");
        $fieldCity->setType("text");
        $fieldCity->setIsRequired(true);
        $fieldCity->setLength(200);

        $fieldStartYear = new \App\Entities\Table();
        $fieldStartYear->setName("startyear");
        $fieldStartYear->setType("text");
        $fieldStartYear->setClass("yearpicker");
        $fieldStartYear->setLength(100);

        $fieldEndYear = new \App\Entities\Table();
        $fieldEndYear->setName("endyear");
        $fieldEndYear->setType("text");
        $fieldEndYear->setClass("yearpicker");
        $fieldEndYear->setLength(100);

        $fieldMajor = new \App\Entities\Table();
        $fieldMajor->setName("major");
        $fieldMajor->setType("text");
        $fieldMajor->setLength(200);

        $fieldStatus = new \App\Entities\Table();
        $fieldStatus->setName("status");
        $fieldStatus->setType("select");
        $fieldStatus->setClass("select2");
        $fieldStatus->setIsRequired(true);
        $fieldStatus->setLength(140);
        $fieldStatus->setField([
            "id"    => "value",
            "text"  => "name"
        ]);

        $statusList = $reference->findBy([
            'sys_reference.name'              => 'StatusEducation',
            'sys_reference.isactive'          => 'Y',
            'sys_ref_detail.isactive'         => 'Y',
        ], null, [
            'field'     => 'sys_ref_detail.name',
            'option'    => 'ASC'
        ])->getResult();

        $fieldStatus->setList($statusList);

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName($this->modelDetail->primaryKey);
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");

        //? Create
        if (empty($set)) {
            $table = [
                $id,
                $this->field->fieldTable($fieldEducation),
                $this->field->fieldTable($fieldSchool),
                $this->field->fieldTable($fieldCity),
                $this->field->fieldTable($fieldStartYear),
                $this->field->fieldTable($fieldEndYear),
                $this->field->fieldTable($fieldMajor),
                $this->field->fieldTable($fieldStatus),
                $this->field->fieldTable($btnDelete)
            ];
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $id = $row->getEmpEducationId();

                $fieldEducation->setValue($row->getEducation());
                $fieldSchool->setValue($row->getSchool());
                $fieldCity->setValue($row->getCity());
                $fieldStartYear->setValue($row->getStartYear());
                $fieldEndYear->setValue($row->getEndYear());
                $fieldMajor->setValue($row->getMajor());
                $fieldStatus->setValue($row->getStatus());
                $btnDelete->setValue($id);

                $table[] = [
                    $id,
                    $this->field->fieldTable($fieldEducation),
                    $this->field->fieldTable($fieldSchool),
                    $this->field->fieldTable($fieldCity),
                    $this->field->fieldTable($fieldStartYear),
                    $this->field->fieldTable($fieldEndYear),
                    $this->field->fieldTable($fieldMajor),
                    $this->field->fieldTable($fieldStatus),
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        return json_encode($table);
    }
}

<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use Config\Services;
use App\Models\M_Employee;
use App\Models\M_EmpCourse;
use App\Models\M_Reference;

class EmpCourse extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Employee($this->request);
        $this->modelDetail = new M_EmpCourse($this->request);
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

                //     if (!$this->validation->run($post, 'reference')) {
                //         $response = $this->field->errorValidation($this->model->table, $post);
                //     } else {
                $response = $this->save();

                if (isset($response[0]["success"])) {
                    if (!isset($post["id"]))
                        $response = message('success', true, notification("insert"));

                    $detail = $this->modelDetail->where($this->model->primaryKey, $post["md_employee_id"])->findAll();
                    $response[0]["line"] = $this->tableLine('edit', $detail);
                }


                // }
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

        $fieldCourse = new \App\Entities\Table();
        $fieldCourse->setName("course");
        $fieldCourse->setType("text");
        $fieldCourse->setIsRequired(true);
        $fieldCourse->setLength(200);

        $fieldIntitution = new \App\Entities\Table();
        $fieldIntitution->setName("intitution");
        $fieldIntitution->setType("text");
        $fieldIntitution->setIsRequired(true);
        $fieldIntitution->setLength(250);

        $fieldLevel = new \App\Entities\Table();
        $fieldLevel->setName("level");
        $fieldLevel->setType("text");
        $fieldLevel->setLength(200);

        $fieldStartDate = new \App\Entities\Table();
        $fieldStartDate->setName("startdate");
        $fieldStartDate->setType("text");
        $fieldStartDate->setIsRequired(true);
        $fieldStartDate->setClass("datepicker");
        $fieldStartDate->setLength(150);

        $fieldEndDate = new \App\Entities\Table();
        $fieldEndDate->setName("enddate");
        $fieldEndDate->setType("text");
        $fieldEndDate->setIsRequired(true);
        $fieldEndDate->setClass("datepicker");
        $fieldEndDate->setLength(150);

        $fieldStatus = new \App\Entities\Table();
        $fieldStatus->setName("status");
        $fieldStatus->setType("select");
        $fieldStatus->setClass("select2");
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
                $this->field->fieldTable($fieldCourse),
                $this->field->fieldTable($fieldIntitution),
                $this->field->fieldTable($fieldLevel),
                $this->field->fieldTable($fieldStartDate),
                $this->field->fieldTable($fieldEndDate),
                $this->field->fieldTable($fieldStatus),
                $this->field->fieldTable($btnDelete)
            ];
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $id = $row->getEmpCoursesId();

                $fieldCourse->setValue($row->getCourse());
                $fieldIntitution->setValue($row->getIntitution());
                $fieldLevel->setValue($row->getLevel());
                $fieldStartDate->setValue($row->getStartDate());
                $fieldEndDate->setValue($row->getEndDate());
                $fieldStatus->setValue($row->getStatus());
                $btnDelete->setValue($id);

                $table[] = [
                    $id,
                    $this->field->fieldTable($fieldCourse),
                    $this->field->fieldTable($fieldIntitution),
                    $this->field->fieldTable($fieldLevel),
                    $this->field->fieldTable($fieldStartDate),
                    $this->field->fieldTable($fieldEndDate),
                    $this->field->fieldTable($fieldStatus),
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        return json_encode($table);
    }
}

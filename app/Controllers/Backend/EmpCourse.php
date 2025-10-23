<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use Config\Services;
use App\Models\M_Employee;
use App\Models\M_EmpCourse;
use App\Models\M_Reference;
use App\Models\M_Skill;

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

                if (!$this->validation->run($post, 'employee_course')) {
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
        $fieldLevel->setIsRequired(true);
        $fieldLevel->setLength(200);

        $fieldStartDate = new \App\Entities\Table();
        $fieldStartDate->setName("startdate");
        $fieldStartDate->setType("text");
        $fieldStartDate->setClass("datepicker");
        $fieldStartDate->setIsRequired(true);
        $fieldStartDate->setLength(150);

        $fieldEndDate = new \App\Entities\Table();
        $fieldEndDate->setName("enddate");
        $fieldEndDate->setType("text");
        $fieldEndDate->setClass("datepicker");
        $fieldEndDate->setIsRequired(true);
        $fieldEndDate->setLength(150);

        $fieldStatus = new \App\Entities\Table();
        $fieldStatus->setName("status");
        $fieldStatus->setType("select");
        $fieldStatus->setClass("select2");
        $fieldStatus->setLength(140);
        $fieldStatus->setIsRequired(true);
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

        $fieldCertificate = new \App\Entities\Table();
        $fieldCertificate->setName("certificate");
        $fieldCertificate->setType("checkbox");
        $fieldCertificate->setClass("active");
        $fieldCertificate->setIsChecked(true);

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName($this->modelDetail->primaryKey);
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");

        // TODO : Set ReadOnly if no role Emp Admin
        if (!$roleEmpAdm) {
            $fieldCourse->setIsReadonly(true);
            $fieldIntitution->setIsReadonly(true);
            $fieldLevel->setIsReadonly(true);
            $fieldStartDate->setIsReadonly(true);
            $fieldEndDate->setIsReadonly(true);
            $fieldStatus->setIsReadonly(true);
            $fieldCertificate->setIsReadonly(true);
            $btnDelete->setIsReadonly(true);
        }

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
                $this->field->fieldTable($fieldCertificate),
                $this->field->fieldTable($btnDelete)
            ];
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $id = $row->getEmpCoursesId();
                $startDate = $row->getStartDate() ? format_dmy($row->getStartDate(), "-") : null;
                $endDate = $row->getEndDate() ? format_dmy($row->getEndDate(), "-") : null;

                $fieldCourse->setValue($row->getCourse());
                $fieldIntitution->setValue($row->getIntitution());
                $fieldLevel->setValue($row->getLevel());
                $fieldStartDate->setValue($startDate);
                $fieldEndDate->setValue($endDate);
                $fieldStatus->setValue($row->getStatus());
                $fieldCertificate->setValue($row->getCertificate());
                $btnDelete->setValue($id);

                if ($row->getCertificate() === "N") {
                    $fieldCertificate->setIsChecked(false);
                } else {
                    $fieldCertificate->setIsChecked(true);
                }

                $table[] = [
                    $id,
                    $this->field->fieldTable($fieldCourse),
                    $this->field->fieldTable($fieldIntitution),
                    $this->field->fieldTable($fieldLevel),
                    $this->field->fieldTable($fieldStartDate),
                    $this->field->fieldTable($fieldEndDate),
                    $this->field->fieldTable($fieldStatus),
                    $this->field->fieldTable($fieldCertificate),
                    $roleEmpAdm ? $this->field->fieldTable($btnDelete) : ''
                ];
            endforeach;
        }

        return json_encode($table);
    }
}

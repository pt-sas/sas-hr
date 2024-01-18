<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use Config\Services;
use App\Models\M_Employee;
use App\Models\M_EmpFamily;
use App\Models\M_Reference;

class EmpFamily extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Employee($this->request);
        $this->modelDetail = new M_EmpFamily($this->request);
        $this->entity = new \App\Entities\Employee();
    }

    public function create()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            $table = json_decode($post['table']);

            //! Mandatory property for detail validation
            $post['line'] = countLine($table);
            $post['detail'] = [
                'table' => arrTableLine($table)
            ];

            try {
                $this->entity->fill($post);

                // if (!$this->validation->run($post, 'reference')) {
                //     $response = $this->field->errorValidation($this->model->table, $post);
                // } else {
                if ($this->isNew())
                    $this->entity->setEmployeeId($post["md_employee_id"]);

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

        $fieldMember = new \App\Entities\Table();
        $fieldMember->setName("member");
        $fieldMember->setType("select");
        $fieldMember->setClass("select2");
        $fieldMember->setIsRequired(true);
        $fieldMember->setLength(150);
        $fieldMember->setField([
            "id"    => "value",
            "text"  => "name"
        ]);

        $memberList = $reference->findBy([
            'sys_reference.name'              => 'MemberOfFamily',
            'sys_reference.isactive'          => 'Y',
            'sys_ref_detail.isactive'         => 'Y',
        ], null, [
            'field'     => 'sys_ref_detail.sys_ref_detail_id',
            'option'    => 'ASC'
        ])->getResult();

        $fieldMember->setList($memberList);

        $fieldName = new \App\Entities\Table();
        $fieldName->setName("name");
        $fieldName->setType("text");
        $fieldName->setIsRequired(true);
        $fieldName->setLength(200);

        $fieldGender = new \App\Entities\Table();
        $fieldGender->setName("gender");
        $fieldGender->setType("select");
        $fieldGender->setClass("select2");
        $fieldGender->setLength(140);
        $fieldGender->setField([
            "id"    => "value",
            "text"  => "name"
        ]);

        $genderList = $reference->findBy([
            'sys_reference.name'              => 'Gender',
            'sys_reference.isactive'          => 'Y',
            'sys_ref_detail.isactive'         => 'Y',
        ], null, [
            'field'     => 'sys_ref_detail.name',
            'option'    => 'ASC'
        ])->getResult();

        $fieldGender->setList($genderList);

        $fieldAge = new \App\Entities\Table();
        $fieldAge->setName("age");
        $fieldAge->setType("text");
        $fieldAge->setClass("number");
        $fieldAge->setLength(70);

        $fieldEducation = new \App\Entities\Table();
        $fieldEducation->setName("education");
        $fieldEducation->setType("select");
        $fieldEducation->setClass("select2");
        $fieldEducation->setLength(140);
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

        $fieldJob = new \App\Entities\Table();
        $fieldJob->setName("job");
        $fieldJob->setType("text");
        $fieldJob->setLength(200);

        $fieldStatus = new \App\Entities\Table();
        $fieldStatus->setName("status");
        $fieldStatus->setType("select");
        $fieldStatus->setClass("select2");
        $fieldStatus->setIsRequired(true);
        $fieldStatus->setLength(150);
        $fieldStatus->setField([
            "id"    => "value",
            "text"  => "name"
        ]);

        $lifeStatus = $reference->findBy([
            'sys_reference.name'              => 'LifeStatus',
            'sys_reference.isactive'          => 'Y',
            'sys_ref_detail.isactive'         => 'Y',
        ], null, [
            'field'     => 'sys_ref_detail.name',
            'option'    => 'ASC'
        ])->getResult();

        $fieldStatus->setList($lifeStatus);

        $fieldDateOfDeath = new \App\Entities\Table();
        $fieldDateOfDeath->setName("dateofdeath");
        $fieldDateOfDeath->setType("text");
        $fieldDateOfDeath->setClass("datepicker");
        $fieldDateOfDeath->setLength(150);

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName($this->modelDetail->primaryKey);
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");

        //? Create
        if (empty($set)) {
            $fieldStatus->setValue($this->Status_Hidup);
            $fieldDateOfDeath->setIsReadonly(true);

            $table = [
                $id,
                $this->field->fieldTable($fieldMember),
                $this->field->fieldTable($fieldName),
                $this->field->fieldTable($fieldGender),
                $this->field->fieldTable($fieldAge),
                $this->field->fieldTable($fieldEducation),
                $this->field->fieldTable($fieldJob),
                $this->field->fieldTable($fieldStatus),
                $this->field->fieldTable($fieldDateOfDeath),
                $this->field->fieldTable($btnDelete)
            ];
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $id = $row->getEmpFamilyId();

                $fieldMember->setValue($row->getMember());
                $fieldName->setValue($row->getName());
                $fieldGender->setValue($row->getGender());
                $fieldAge->setValue($row->getAge());
                $fieldEducation->setValue($row->getEducation());
                $fieldJob->setValue($row->getJob());
                $fieldStatus->setValue($row->getStatus());
                $fieldDateOfDeath->setValue($row->getDateOfDeath());

                if ($row->getStatus() === $this->Status_Hidup)
                    $fieldDateOfDeath->setIsReadonly(true);
                else
                    $fieldDateOfDeath->setIsReadonly(false);

                $btnDelete->setValue($id);

                $table[] = [
                    $id,
                    $this->field->fieldTable($fieldMember),
                    $this->field->fieldTable($fieldName),
                    $this->field->fieldTable($fieldGender),
                    $this->field->fieldTable($fieldAge),
                    $this->field->fieldTable($fieldEducation),
                    $this->field->fieldTable($fieldJob),
                    $this->field->fieldTable($fieldStatus),
                    $this->field->fieldTable($fieldDateOfDeath),
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        return json_encode($table);
    }
}

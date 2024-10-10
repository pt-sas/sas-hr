<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use Config\Services;
use App\Models\M_Employee;
use App\Models\M_EmpSkill;
use App\Models\M_Reference;

class EmpLanguage extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Employee($this->request);
        $this->modelDetail = new M_EmpSkill($this->request);
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

                if (!$this->validation->run($post, 'employee_language')) {
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
                $detail = $this->modelDetail->where([
                    $this->model->primaryKey    => $id,
                    "skilltype"                 => "B"
                ])->findAll();

                if (isset($get["md_employee_id"])) {
                    $list = $this->model->where($this->model->primaryKey, $get["md_employee_id"])->findAll();
                    $detail = $this->modelDetail->where([
                        $this->model->primaryKey    => $get["md_employee_id"],
                        "skilltype"                 => "B"
                    ])->findAll();
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

        $table = [];
        $id = 0;

        $fieldName = new \App\Entities\Table();
        $fieldName->setName("name");
        $fieldName->setType("text");
        $fieldName->setIsRequired(true);
        $fieldName->setLength(200);

        $fieldWriteAbility = new \App\Entities\Table();
        $fieldWriteAbility->setName("written_ability");
        $fieldWriteAbility->setType("select");
        $fieldWriteAbility->setClass("select2");
        $fieldWriteAbility->setIsRequired(true);
        $fieldWriteAbility->setField([
            "id"    => "value",
            "text"  => "name"
        ]);

        $writtenAbilityList = $reference->findBy([
            'sys_reference.name'              => 'NumberList',
            'sys_reference.isactive'          => 'Y',
            'sys_ref_detail.isactive'         => 'Y',
        ], null, [
            'field'     => 'sys_ref_detail.sys_ref_detail_id',
            'option'    => 'ASC'
        ])->getResult();

        $fieldWriteAbility->setList($writtenAbilityList);
        $fieldWriteAbility->setLength(150);

        $fieldVerbalAbility = new \App\Entities\Table();
        $fieldVerbalAbility->setName("verbal_ability");
        $fieldVerbalAbility->setType("select");
        $fieldVerbalAbility->setClass("select2");
        $fieldVerbalAbility->setIsRequired(true);
        $fieldVerbalAbility->setField([
            "id"    => "value",
            "text"  => "name"
        ]);

        $verbalAbilityList = $reference->findBy([
            'sys_reference.name'              => 'NumberList',
            'sys_reference.isactive'          => 'Y',
            'sys_ref_detail.isactive'         => 'Y',
        ], null, [
            'field'     => 'sys_ref_detail.sys_ref_detail_id',
            'option'    => 'ASC'
        ])->getResult();

        $fieldVerbalAbility->setList($verbalAbilityList);
        $fieldVerbalAbility->setLength(150);

        $fieldSkillType = new \App\Entities\Table();
        $fieldSkillType->setName("skilltype");
        $fieldSkillType->setType("select");
        $fieldSkillType->setClass("select2");
        $fieldSkillType->setLength(140);
        $fieldSkillType->setField([
            "id"    => "value",
            "text"  => "name"
        ]);

        $skillList = $reference->findBy([
            'sys_reference.name'              => 'SkillType',
            'sys_reference.isactive'          => 'Y',
            'sys_ref_detail.isactive'         => 'Y',
        ], null, [
            'field'     => 'sys_ref_detail.name',
            'option'    => 'ASC'
        ])->getResult();

        $fieldSkillType->setList($skillList);
        $fieldSkillType->setValue("B");
        $fieldSkillType->setIsReadonly(true);

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName($this->modelDetail->primaryKey);
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");

        //? Create
        if (empty($set)) {
            $table = [
                $id,
                $this->field->fieldTable($fieldName),
                $this->field->fieldTable($fieldWriteAbility),
                $this->field->fieldTable($fieldVerbalAbility),
                $this->field->fieldTable($fieldSkillType),
                $this->field->fieldTable($btnDelete)
            ];
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $id = $row->getEmpSkillsId();

                $fieldName->setValue($row->getName());
                $fieldWriteAbility->setValue($row->getWrittenAbility());
                $fieldVerbalAbility->setValue($row->getVerbalAbility());
                $fieldSkillType->setValue($row->getSkillType());
                $btnDelete->setValue($id);

                $table[] = [
                    $id,
                    $this->field->fieldTable($fieldName),
                    $this->field->fieldTable($fieldWriteAbility),
                    $this->field->fieldTable($fieldVerbalAbility),
                    $this->field->fieldTable($fieldSkillType),
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        return json_encode($table);
    }
}

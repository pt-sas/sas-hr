<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use Config\Services;
use App\Models\M_Employee;
use App\Models\M_EmpSkill;
use App\Models\M_Reference;
use App\Models\M_Skill;

class EmpSkill extends BaseController
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

                if (!$this->validation->run($post, 'employee_skill')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $response = $this->save();

                    if (isset($response[0]["success"])) {
                        if (!isset($post["id"]))
                            $response = message('success', true, notification("insert"));

                        $detail = $this->modelDetail->where([
                            $this->model->primaryKey    => $post["md_employee_id"],
                            "skilltype"                 => "K"
                        ])->findAll();

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
                    "skilltype"                 => "K"
                ])->findAll();

                if (isset($get["md_employee_id"])) {
                    $list = $this->model->where($this->model->primaryKey, $get["md_employee_id"])->findAll();
                    $detail = $this->modelDetail->where([
                        $this->model->primaryKey    => $get["md_employee_id"],
                        "skilltype"                 => "K"
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
        $mSkill = new M_Skill($this->request);
        $roleEmpAdm = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_Admin');

        $table = [];
        $id = 0;

        $fieldName = new \App\Entities\Table();
        $fieldName->setName("name");
        $fieldName->setType("select");
        $fieldName->setClass("select2");
        $fieldName->setIsRequired(true);
        $fieldName->setField([
            "id"    => "name",
            "text"  => "name"
        ]);

        $skills = $mSkill->where("isactive", "Y")->orderBy("name", "ASC")->findAll();
        $fieldName->setList($skills);
        $fieldName->setLength(200);

        $fieldAbility = new \App\Entities\Table();
        $fieldAbility->setName("ability");
        $fieldAbility->setType("select");
        $fieldAbility->setClass("select2");
        $fieldAbility->setIsRequired(true);
        $fieldAbility->setField([
            "id"    => "value",
            "text"  => "name"
        ]);

        $abilityList = $reference->findBy([
            'sys_reference.name'              => 'NumberList',
            'sys_reference.isactive'          => 'Y',
            'sys_ref_detail.isactive'         => 'Y',
        ], null, [
            'field'     => 'sys_ref_detail.sys_ref_detail_id',
            'option'    => 'ASC'
        ])->getResult();

        $fieldAbility->setList($abilityList);
        $fieldAbility->setLength(150);

        $fieldSkillType = new \App\Entities\Table();
        $fieldSkillType->setName("skilltype");
        $fieldSkillType->setType("select");
        $fieldSkillType->setClass("select2");
        $fieldSkillType->setIsReadonly(true);
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
        $fieldSkillType->setValue("K");
        $fieldSkillType->setLength(140);

        $fieldDesc = new \App\Entities\Table();
        $fieldDesc->setName("description");
        $fieldDesc->setType("text");
        $fieldDesc->setIsRequired(true);
        $fieldDesc->setLength(300);

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName($this->modelDetail->primaryKey);
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");

        // TODO : Set ReadOnly if no role Emp Admin
        if (!$roleEmpAdm) {
            $fieldName->setIsReadonly(true);
            $fieldDesc->setIsReadonly(true);
            $fieldAbility->setIsReadonly(true);
            $fieldSkillType->setIsReadonly(true);
            $btnDelete->setIsReadonly(true);
        }

        //? Create
        if (empty($set)) {
            $table = [
                $id,
                $this->field->fieldTable($fieldName),
                $this->field->fieldTable($fieldDesc),
                $this->field->fieldTable($fieldAbility),
                $this->field->fieldTable($fieldSkillType),
                $this->field->fieldTable($btnDelete)
            ];
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $id = $row->getEmpSkillsId();

                $fieldName->setValue($row->getName());
                $fieldDesc->setValue($row->getDescription());
                $fieldAbility->setValue($row->getAbility());
                $fieldSkillType->setValue($row->getSkillType());
                $btnDelete->setValue($id);

                $table[] = [
                    $id,
                    $this->field->fieldTable($fieldName),
                    $this->field->fieldTable($fieldDesc),
                    $this->field->fieldTable($fieldAbility),
                    $this->field->fieldTable($fieldSkillType),
                    $roleEmpAdm ? $this->field->fieldTable($btnDelete) : ''
                ];
            endforeach;
        }

        return json_encode($table);
    }
}

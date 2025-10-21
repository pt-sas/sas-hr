<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_BenefitDetail;
use Config\Services;
use App\Models\M_Employee;
use App\Models\M_EmpBenefit;
use App\Models\M_Reference;

class EmpBenefit extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Employee($this->request);
        $this->modelDetail = new M_EmpBenefit($this->request);
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

                if (!$this->validation->run($post, 'employee_benefit')) {
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
        $mBenefitDetail = new M_BenefitDetail($this->request);
        $roleEmpAdm = $this->access->getUserRoleName($this->session->get('sys_user_id'), 'W_Emp_Admin');
        $table = [];
        $id = 0;

        $fieldBenefitType = new \App\Entities\Table();
        $fieldBenefitType->setName("benefit");
        $fieldBenefitType->setType("select");
        $fieldBenefitType->setClass("select2");
        $fieldBenefitType->setIsRequired(true);
        $fieldBenefitType->setLength(150);
        $fieldBenefitType->setField([
            "id"    => "value",
            "text"  => "name"
        ]);

        $benefitList = $reference->findBy([
            'sys_reference.name'              => 'BenefitType',
            'sys_reference.isactive'          => 'Y',
            'sys_ref_detail.isactive'         => 'Y',
        ], null, [
            'field'     => 'sys_ref_detail.name',
            'option'    => 'ASC'
        ])->getResult();

        $fieldBenefitType->setList($benefitList);

        $fieldStatus = new \App\Entities\Table();
        $fieldStatus->setName("status");
        $fieldStatus->setType("select");
        $fieldStatus->setClass("select2");
        $fieldStatus->setIsRequired(true);
        $fieldStatus->setLength(100);
        $fieldStatus->setField([
            "id" => "value",
            "text" => "name"
        ]);

        $statusList = $reference->findBy([
            'sys_reference.name'              => 'StatusBenefit',
            'sys_reference.isactive'          => 'Y',
            'sys_ref_detail.isactive'         => 'Y',
        ], null, [
            'field'     => 'sys_ref_detail.name',
            'option'    => 'ASC'
        ])->getResult();

        $fieldStatus->setList($statusList);



        $fieldIsDetail = new \App\Entities\Table();
        $fieldIsDetail->setName("isdetail");
        $fieldIsDetail->setType("select");
        $fieldIsDetail->setClass("select2");
        $fieldIsDetail->setIsRequired(true);
        $fieldIsDetail->setLength(100);
        $fieldIsDetail->setField([
            "id" => "value",
            "text" => "name"
        ]);

        $detailList = $reference->findBy(
            [
                'sys_reference.name'              => 'StatusActive',
                'sys_reference.isactive'          => 'Y',
                'sys_ref_detail.isactive'         => 'Y',
            ],
            null,
            [
                'field'     => 'sys_ref_detail.name',
                'option'    => 'DESC'
            ]
        )->getResult();

        $fieldIsDetail->setList($detailList);

        $fieldDescription = new \App\Entities\Table();
        $fieldDescription->setName("description");
        $fieldDescription->setType("text");
        $fieldDescription->setClass("text");
        $fieldDescription->setIsRequired(true);
        $fieldDescription->setLength(250);

        $fieldDetail = new \App\Entities\Table();
        $fieldDetail->setTitle("Detail");
        $fieldDetail->setName("button_detail");
        $fieldDetail->setType("button");
        $fieldDetail->setClass("btn-primary btn_isdetailbenefit numeric");
        $fieldDetail->setIsReadonly(true);
        $fieldDetail->setValue(0);

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName($this->modelDetail->primaryKey);
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");

        // TODO : Set ReadOnly if no role Emp Admin
        if (!$roleEmpAdm) {
            $fieldBenefitType->setIsReadonly(true);
            $fieldStatus->setIsReadonly(true);
            $fieldDescription->setIsReadonly(true);
            $fieldIsDetail->setIsReadonly(true);
            $fieldDetail->setIsReadonly(true);
            $btnDelete->setIsReadonly(true);
        }

        //? Create
        if (empty($set)) {
            $table = [
                $id,
                $this->field->fieldTable($fieldBenefitType),
                $this->field->fieldTable($fieldStatus),
                $this->field->fieldTable($fieldDescription),
                $this->field->fieldTable($fieldIsDetail),
                $this->field->fieldTable($fieldDetail),
                $this->field->fieldTable($btnDelete)
            ];
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $id = $row->getEmpBenefitId();
                $count = $mBenefitDetail->countAll($this->modelDetail->primaryKey, $id);

                $fieldBenefitType->setValue($row->getBenefit());
                $fieldStatus->setValue($row->getStatus());
                $fieldIsDetail->setValue($row->getIsDetail());
                $fieldDescription->setValue($row->getDescription());

                $fieldDetail->setValue($count);
                $fieldDetail->setId($id);
                if ($row->getIsDetail() === "Y") {
                    $fieldDetail->setIsReadonly(false);
                } else {
                    $fieldDetail->setIsReadonly(true);
                }

                $btnDelete->setValue($id);

                $table[] = [
                    $id,
                    $this->field->fieldTable($fieldBenefitType),
                    $this->field->fieldTable($fieldStatus),
                    $this->field->fieldTable($fieldDescription),
                    $this->field->fieldTable($fieldIsDetail),
                    $this->field->fieldTable($fieldDetail),
                    $roleEmpAdm ? $this->field->fieldTable($btnDelete) : ''
                ];
            endforeach;
        }

        return json_encode($table);
    }

    public function getBy($id)
    {
        if ($this->request->isAJAX()) {
            $response = [];

            try {
                $row = $this->modelDetail->find($id);
                $response['text'] = $row->getBenefit();
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}

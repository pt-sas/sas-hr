<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_BenefitDetail;
use App\Models\M_EmpBenefit;
use App\Models\M_Reference;
use Config\Services;

class BenefitDetail extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_EmpBenefit($this->request);
        $this->modelDetail = new M_BenefitDetail($this->request);
        $this->entity = new \App\Entities\EmpBenefit();
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

            $foreignKey = $post[$this->model->primaryKey];

            try {
                if ($this->isNew())
                    $this->entity->setEmpBenefitId($foreignKey);

                if (!$this->validation->run($post, 'benefit_detail')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $response = $this->save();

                    if (isset($response[0]["success"])) {
                        if (!isset($post["id"]))
                            $response = message('success', true, notification("insert"));

                        $detail = $this->modelDetail->where($this->model->primaryKey, $foreignKey)->findAll();
                        $response[0]["line"] = $this->tableLine('edit', $detail);
                    }

                    $response[0]["all"] = $post;
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

                if (isset($get[$this->model->primaryKey])) {
                    $foreignKey = $get[$this->model->primaryKey];
                    $list = $this->model->where($this->model->primaryKey, $foreignKey)->findAll();
                    $detail = $this->modelDetail->where($this->model->primaryKey, $foreignKey)->findAll();
                }

                if ($detail) {
                    $fieldHeader = new \App\Entities\Table();
                    $fieldHeader->setTable($this->model->table);
                    $fieldHeader->setList($list);

                    $result = [
                        'header'    => $this->field->store($fieldHeader),
                        'line'      => $this->tableLine('edit', $detail, $list[0]->benefit)
                    ];
                }

                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }

    public function tableLine($set = null, $detail = [], $benefit = null)
    {
        $post = $this->request->getVar();
        $reference = new M_Reference($this->request);

        $table = [];
        $id = 0;

        $fieldBenefitDetail = new \App\Entities\Table();
        $fieldBenefitDetail->setName("benefit_detail");
        $fieldBenefitDetail->setType("select");
        $fieldBenefitDetail->setClass("select2");
        $fieldBenefitDetail->setIsRequired(true);
        $fieldBenefitDetail->setLength(200);
        $fieldBenefitDetail->setField([
            "id"    => "value",
            "text"  => "name"
        ]);

        if ($benefit) {
            $benefitList = $reference->findBy(
                [
                    'sys_reference.name'              => "$benefit",
                    'sys_reference.isactive'          => 'Y',
                    'sys_ref_detail.isactive'         => 'Y',
                ],
                null,
                [
                    'field'     => 'sys_ref_detail.name',
                    'option'    => 'ASC'
                ]
            )->getResult();
        } else {
            $benefitList = $reference->findBy(
                [
                    'sys_reference.name'              => "{$post['md_employee_benefit_id']}",
                    'sys_reference.isactive'          => 'Y',
                    'sys_ref_detail.isactive'         => 'Y',
                ],
                null,
                [
                    'field'     => 'sys_ref_detail.name',
                    'option'    => 'ASC'
                ]
            )->getResult();
        }

        $fieldBenefitDetail->setList($benefitList);

        // $fieldStatusDetail = new \App\Entities\Table();
        // $fieldStatusDetail->setName("status");
        // $fieldStatusDetail->setType("select");
        // $fieldStatusDetail->setClass("select2");
        // $fieldStatusDetail->setIsRequired(true);
        // $fieldStatusDetail->setLength(100);
        // $fieldStatusDetail->setField([
        //     "id" => "value",
        //     "text" => "name"
        // ]);

        // $statusList = $reference->findBy(
        //     [
        //         'sys_reference.name'              => 'StatusBenefit',
        //         'sys_reference.isactive'          => 'Y',
        //         'sys_ref_detail.isactive'         => 'Y',
        //     ],
        //     null,
        //     [
        //         'field'     => 'sys_ref_detail.name',
        //         'option'    => 'ASC'
        //     ]
        // )->getResult();

        // $fieldStatusDetail->setList($statusList);

        $fieldDescription = new \App\Entities\Table();
        $fieldDescription->setName("description");
        $fieldDescription->setType("text");
        $fieldDescription->setIsRequired(false);
        $fieldDescription->setLength(250);

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName($this->modelDetail->primaryKey);
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");

        //? Create
        if (empty($set)) {
            $table = [
                $id,
                $this->field->fieldTable($fieldBenefitDetail),
                // $this->field->fieldTable($fieldStatusDetail),
                $this->field->fieldTable($fieldDescription),
                $this->field->fieldTable($btnDelete)
            ];
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $id = $row->getBenefitDetailId();

                $fieldBenefitDetail->setValue($row->getBenefitDetail());
                // $fieldStatusDetail->setValue($row->getStatus());
                $fieldDescription->setValue($row->getDescription());
                $btnDelete->setValue($id);

                $table[] = [
                    $id,
                    $this->field->fieldTable($fieldBenefitDetail),
                    // $this->field->fieldTable($fieldStatusDetail),
                    $this->field->fieldTable($fieldDescription),
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        return json_encode($table);
        // return json_encode($benefitList);
    }
}

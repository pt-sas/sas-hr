<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Reference;
use App\Models\M_Rule;
use App\Models\M_RuleDetail;
use App\Models\M_RuleValue;
use Config\Services;

class RuleDetail extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Rule($this->request);
        $this->modelDetail = new M_RuleDetail($this->request);
        $this->entity = new \App\Entities\Rule();
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
                    $this->entity->setRuleId($post["md_rule_id"]);

                if (!$this->validation->run($post, 'rule_detail')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $response = $this->save();

                    if (isset($response[0]["success"])) {
                        if (!isset($post["id"]))
                            $response = message('success', true, notification("insert"));

                        $detail = $this->modelDetail->where($this->model->primaryKey, $post[$this->model->primaryKey])->findAll();
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
        $mReference = new M_Reference($this->request);
        $mRuleVal = new M_RuleValue($this->request);

        $table = [];
        $id = 0;

        $fieldName = new \App\Entities\Table();
        $fieldName->setName("name");
        $fieldName->setType("text");
        $fieldName->setIsRequired(true);
        $fieldName->setLength(200);

        $fieldDesc = new \App\Entities\Table();
        $fieldDesc->setName("description");
        $fieldDesc->setType("text");
        $fieldDesc->setLength(200);

        $fieldOperation = new \App\Entities\Table();
        $fieldOperation->setName("operation");
        $fieldOperation->setType("select");
        $fieldOperation->setClass("select2");
        $fieldOperation->setLength(100);
        $fieldOperation->setField([
            "id"    => "value",
            "text"  => "name"
        ]);
        $operationList = $mReference->findBy([
            'sys_reference.name'              => 'Operation',
            'sys_reference.isactive'          => 'Y',
            'sys_ref_detail.isactive'         => 'Y',
        ], null, [
            'field'     => 'sys_ref_detail.name',
            'option'    => 'ASC'
        ])->getResult();
        $fieldOperation->setList($operationList);

        $fieldFormatCond = new \App\Entities\Table();
        $fieldFormatCond->setName("format_condition");
        $fieldFormatCond->setType("select");
        $fieldFormatCond->setClass("select2");
        $fieldFormatCond->setLength(150);
        $fieldFormatCond->setField([
            "id"    => "value",
            "text"  => "name"
        ]);
        $formatCondList = $mReference->findBy([
            'sys_reference.name'              => 'FormatData',
            'sys_reference.isactive'          => 'Y',
            'sys_ref_detail.isactive'         => 'Y',
        ], null, [
            'field'     => 'sys_ref_detail.name',
            'option'    => 'ASC'
        ])->getResult();
        $fieldFormatCond->setList($formatCondList);

        $fieldCondition = new \App\Entities\Table();
        $fieldCondition->setName("condition");
        $fieldCondition->setType("text");
        $fieldCondition->setLength(150);

        $fieldFormatVal = new \App\Entities\Table();
        $fieldFormatVal->setName("format_value");
        $fieldFormatVal->setType("select");
        $fieldFormatVal->setClass("select2");
        $fieldFormatVal->setLength(150);
        $fieldFormatVal->setField([
            "id"    => "value",
            "text"  => "name"
        ]);
        $formatValList = $mReference->findBy([
            'sys_reference.name'              => 'FormatData',
            'sys_reference.isactive'          => 'Y',
            'sys_ref_detail.isactive'         => 'Y',
        ], null, [
            'field'     => 'sys_ref_detail.name',
            'option'    => 'ASC'
        ])->getResult();
        $fieldFormatVal->setList($formatValList);

        $fieldValue = new \App\Entities\Table();
        $fieldValue->setName("value");
        $fieldValue->setType("text");
        $fieldValue->setLength(150);

        $fieldDetail = new \App\Entities\Table();
        $fieldDetail->setTitle("Detail");
        $fieldDetail->setName("isdetail");
        $fieldDetail->setType("button");
        $fieldDetail->setClass("btn-primary btn_isdetail numeric");
        $fieldDetail->setIsReadonly(true);
        $fieldDetail->setValue(0);

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName($this->modelDetail->primaryKey);
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");

        //? Create
        if (empty($set)) {
            $table = [
                $id,
                $this->field->fieldTable($fieldName),
                $this->field->fieldTable($fieldDesc),
                $this->field->fieldTable($fieldOperation),
                $this->field->fieldTable($fieldFormatCond),
                $this->field->fieldTable($fieldCondition),
                $this->field->fieldTable($fieldFormatVal),
                $this->field->fieldTable($fieldValue),
                $this->field->fieldTable($fieldDetail),
                $this->field->fieldTable($btnDelete)
            ];
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $id = $row->getRuleDetailId();
                $count = $mRuleVal->countAll($this->modelDetail->primaryKey, $id);

                $fieldName->setValue($row->getName());
                $fieldDesc->setValue($row->getDescription());
                $fieldOperation->setValue($row->getOperation());
                $fieldFormatCond->setValue($row->getFormatCondition());
                $fieldCondition->setValue($row->getCondition());
                $fieldFormatVal->setValue($row->getFormatValue());
                $fieldValue->setValue($row->getValue());

                $fieldDetail->setValue($count);
                $fieldDetail->setId($id);
                $fieldDetail->setIsReadonly(false);

                $btnDelete->setValue($id);

                $table[] = [
                    $id,
                    $this->field->fieldTable($fieldName),
                    $this->field->fieldTable($fieldDesc),
                    $this->field->fieldTable($fieldOperation),
                    $this->field->fieldTable($fieldFormatCond),
                    $this->field->fieldTable($fieldCondition),
                    $this->field->fieldTable($fieldFormatVal),
                    $this->field->fieldTable($fieldValue),
                    $this->field->fieldTable($fieldDetail),
                    $this->field->fieldTable($btnDelete)
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
                $response['text'] = $row->getName();
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}

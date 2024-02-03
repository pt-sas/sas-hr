<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_RuleDetail;
use App\Models\M_RuleValue;
use Config\Services;

class RuleValue extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_RuleDetail($this->request);
        $this->modelDetail = new M_RuleValue($this->request);
        $this->entity = new \App\Entities\RuleDetail();
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
                    $this->entity->setRuleDetailId($foreignKey);

                if (!$this->validation->run($post, 'rule_value')) {
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
        $table = [];
        $id = 0;

        $fieldName = new \App\Entities\Table();
        $fieldName->setName("name");
        $fieldName->setType("text");
        $fieldName->setIsRequired(true);
        $fieldName->setLength(400);

        $fieldValue = new \App\Entities\Table();
        $fieldValue->setName("value");
        $fieldValue->setType("text");
        $fieldValue->setIsRequired(true);
        $fieldValue->setLength(400);

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName($this->modelDetail->primaryKey);
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");

        //? Create
        if (empty($set)) {
            $table = [
                $id,
                $this->field->fieldTable($fieldName),
                $this->field->fieldTable($fieldValue),
                $this->field->fieldTable($btnDelete)
            ];
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $id = $row->getRuleValueId();

                $fieldName->setValue($row->getName());
                $fieldValue->setValue($row->getValue());
                $btnDelete->setValue($id);

                $table[] = [
                    $id,
                    $this->field->fieldTable($fieldName),
                    $this->field->fieldTable($fieldValue),
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        return json_encode($table);
    }
}

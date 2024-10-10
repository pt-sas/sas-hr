<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use Config\Services;
use App\Models\M_Employee;
use App\Models\M_EmpWorkDay;
use App\Models\M_Work;

class EmpWorkDay extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Employee($this->request);
        $this->modelDetail = new M_EmpWorkDay($this->request);
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

                // if (!$this->validation->run($post, 'employee_job')) {
                //     $response = $this->field->errorValidation($this->model->table, $post);
                // } else {
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
        $mWork = new M_Work($this->request);

        $table = [];
        $id = 0;

        $fieldWork = new \App\Entities\Table();
        $fieldWork->setName("md_work_id");
        $fieldWork->setType("select");
        $fieldWork->setClass("select2");
        $fieldWork->setIsRequired(true);
        $fieldWork->setField([
            "id"    => "md_work_id",
            "text"  => "name"
        ]);

        $workList = $mWork->where('isactive', 'Y')->orderBy('name', 'ASC')->findAll();

        $fieldWork->setList($workList);
        $fieldWork->setLength(150);

        $fieldValidFrom = new \App\Entities\Table();
        $fieldValidFrom->setName("validfrom");
        $fieldValidFrom->setType("text");
        $fieldValidFrom->setClass("datepicker");
        $fieldValidFrom->setIsRequired(true);
        $fieldValidFrom->setLength(130);

        $fieldValidTo = new \App\Entities\Table();
        $fieldValidTo->setName("validto");
        $fieldValidTo->setType("text");
        $fieldValidTo->setClass("datepicker");
        $fieldValidTo->setIsRequired(true);
        $fieldValidTo->setLength(130);

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName($this->modelDetail->primaryKey);
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");

        //? Create
        if (empty($set)) {
            $table = [
                $id,
                $this->field->fieldTable($fieldWork),
                $this->field->fieldTable($fieldValidFrom),
                $this->field->fieldTable($fieldValidTo),
                $this->field->fieldTable($btnDelete)
            ];
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $id = $row->md_emloyee_work_id;
                $startDate = $row->validfrom ? format_dmy($row->validfrom, "-") : null;
                $endDate = $row->validto ? format_dmy($row->validto, "-") : null;

                $fieldWork->setValue($row->md_work_id);
                $fieldValidFrom->setValue($startDate);
                $fieldValidTo->setValue($endDate);
                $btnDelete->setValue($id);

                $table[] = [
                    $id,
                    $this->field->fieldTable($fieldWork),
                    $this->field->fieldTable($fieldValidFrom),
                    $this->field->fieldTable($fieldValidTo),
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        return json_encode($table);
    }
}

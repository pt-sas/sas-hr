<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use Config\Services;
use App\Models\M_Employee;
use App\Models\M_EmpLicense;
use App\Models\M_Reference;

class EmpLicense extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Employee($this->request);
        $this->modelDetail = new M_EmpLicense($this->request);
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

        $fieldLicenseType = new \App\Entities\Table();
        $fieldLicenseType->setName("licensetype");
        $fieldLicenseType->setType("select");
        $fieldLicenseType->setClass("select2");
        $fieldLicenseType->setLength(200);
        $fieldLicenseType->setField([
            "id"    => "value",
            "text"  => "name"
        ]);

        $licenseList = $reference->findBy([
            'sys_reference.name'              => 'DriverLicenseType',
            'sys_reference.isactive'          => 'Y',
            'sys_ref_detail.isactive'         => 'Y',
        ], null, [
            'field'     => 'sys_ref_detail.name',
            'option'    => 'ASC'
        ])->getResult();

        $fieldLicenseType->setList($licenseList);

        $fieldLicenseNo = new \App\Entities\Table();
        $fieldLicenseNo->setName("license_id");
        $fieldLicenseNo->setType("text");
        $fieldLicenseNo->setClass("number");
        $fieldLicenseNo->setIsRequired(true);
        $fieldLicenseNo->setLength(250);

        $fieldExpiredDate = new \App\Entities\Table();
        $fieldExpiredDate->setName("expireddate");
        $fieldExpiredDate->setType("text");
        $fieldExpiredDate->setClass("datepicker");
        $fieldExpiredDate->setIsRequired(true);
        $fieldExpiredDate->setLength(150);

        $btnDelete = new \App\Entities\Table();
        $btnDelete->setName($this->modelDetail->primaryKey);
        $btnDelete->setType("button");
        $btnDelete->setClass("delete");

        //? Create
        if (empty($set)) {
            $table = [
                $id,
                $this->field->fieldTable($fieldLicenseType),
                $this->field->fieldTable($fieldLicenseNo),
                $this->field->fieldTable($fieldExpiredDate),
                $this->field->fieldTable($btnDelete)
            ];
        }

        //? Update
        if (!empty($set) && count($detail) > 0) {
            foreach ($detail as $row) :
                $id = $row->getEmpLicenseId();

                $fieldLicenseType->setValue($row->getLicenseType());
                $fieldLicenseNo->setValue($row->getLicenseNo());
                $fieldExpiredDate->setValue($row->getExpiredDate());
                $btnDelete->setValue($id);

                $table[] = [
                    $id,
                    $this->field->fieldTable($fieldLicenseType),
                    $this->field->fieldTable($fieldLicenseNo),
                    $this->field->fieldTable($fieldExpiredDate),
                    $this->field->fieldTable($btnDelete)
                ];
            endforeach;
        }

        return json_encode($table);
    }
}

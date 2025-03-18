<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Configuration;
use Config\Services;

class Configuration extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Configuration($this->request);
        $this->entity = new \App\Entities\Configuration();
    }

    public function index()
    {
        return $this->template->render('backend/configuration/config/form_configuration');
    }

    public function showAll()
    {
        if ($this->request->isAJAX()) {
            try {
                $list = $this->model->findAll();

                $data = [];
                foreach ($list as $value) {
                    $data[] = [
                        'field' => strtolower($value->name),
                        'label' => $value->value
                    ];
                }

                $result = [
                    'header'  => $data
                ];

                $response = message('success', true, $result);
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }
        }

        return $this->response->setJSON($response);
    }

    public function create()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            try {
                if (!$this->validation->run($post, 'config')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    foreach ($post as $key => $value) {
                        $fieldData = $this->model->where('name', $key)->first();

                        if ($fieldData) {
                            $this->entity = new \App\Entities\Configuration();
                            $this->entity->sys_configuration_id = $fieldData->sys_configuration_id;
                            $this->entity->value = $value;

                            $this->model->save($this->entity);
                        }
                    }

                    $response = message('success', true, 'Data berhasil disimpan');
                    // $response[0]['insert_id'] = $this->model->getInsertID();
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}
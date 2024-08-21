<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Attendance;
use App\Models\M_Employee;
use CodeIgniter\Config\Services;

class AbsentManual extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Attendance($this->request);
        $this->entity = new \App\Entities\Attendance();
    }

    public function index()
    {
        $data = [
            'today'     => date('d-M-Y')
        ];
        return $this->template->render('transaction/absentmanual/v_absent_manual', $data);
    }

    public function create()
    {
        $mEmployee = new M_Employee($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            $post['checktime'] = date('Y-m-d', strtotime($post['date'])) . " " . $post['time'];

            try {
                if (!$this->validation->run($post, 'absentManual')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
                    $employee = $mEmployee->where('md_employee_id', $post['md_employee_id'])->first();
                    $post["nik"] = $employee->nik;

                    $this->entity->fill($post);

                    $response = $this->save();
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}

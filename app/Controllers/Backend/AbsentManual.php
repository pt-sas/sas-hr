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
            'timestamp' => strtotime(date('Y-m-d H:i:s'))
        ];

        return $this->template->render('transaction/absentmanual/v_absent_manual', $data);
    }

    public function showAll()
    {
        $post = $this->request->getVar();

        $recordTotal = 0;
        $recordsFiltered = 0;
        $data = [];
        $today = date('Y-m-d');

        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelectDetail();
            $join = $this->model->getJoinDetail();
            $order = $this->request->getPost('columns');
            $search = $this->request->getPost('search');
            $sort = ['checktime' => 'ASC', 'nik' => 'ASC'];

            $where['date_format(checktime, "%Y-%m-%d")'] = $today;
            $where[$table . '.created_by'] = $this->access->getSessionUser();
            $where['serialnumber'] = "";

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $val) :
                $row = [];

                $number++;

                $row[] = $number;
                $row[] = $val->nik;
                $row[] = $val->fullname;
                $row[] = format_dmy($val->checktime, "-");
                $row[] = format_time($val->checktime, "-");
                $data[] = $row;
            endforeach;

            $recordTotal = count($data);
            $recordsFiltered = count($data);
        }

        $result = [
            'draw'              => $this->request->getPost('draw'),
            'recordsTotal'      => $recordTotal,
            'recordsFiltered'   => $recordsFiltered,
            'data'              => $data
        ];

        return $this->response->setJSON($result);
    }

    public function create()
    {
        $mEmployee = new M_Employee($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            try {
                $this->entity->nik = $post['nik'];
                $this->entity->checktime = $post['checktime'];

                $response = $this->save();
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}

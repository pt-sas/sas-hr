<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_LeaveBalance;
use Config\Services;

class Rpt_LeaveBalance extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_LeaveBalance($this->request);
    }

    public function index()
    {
        $date = format_dmy(date('Y-m-d'), "-");

        $data = [
            'date_range' => $date . ' - ' . $date
        ];

        return $this->template->render('report/leavebalance/v_leavebalance', $data);
    }

    public function showAll()
    {
        $post = $this->request->getVar();
        $data = [];

        $recordTotal = 0;
        $recordsFiltered = 0;

        if ($this->request->getMethod(true) === 'POST') {
            if (isset($post['form']) && $post['clear'] === 'false') {
                $table = $this->model->table;
                $select = $this->model->getSelect();
                $join = $this->model->getJoin();
                $order = $this->request->getPost('columns');
                $sort = ['md_employee.fullname' => 'ASC', 'trx_leavebalance.submissiondate' => 'ASC'];
                $search = $this->request->getPost('search');

                $number = $this->request->getPost('start');
                $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join);

                foreach ($list as $value) :
                    $row = [];

                    $number++;

                    $row[] = $number;
                    $row[] = $value->employee_fullname;
                    $row[] = format_dmy($value->submissiondate, "-");
                    $row[] = $value->amount;
                    $row[] = $value->documentno;
                    $row[] = "";
                    $row[] = $value->description;
                    $data[] = $row;

                endforeach;

                $recordTotal = $this->datatable->countAll($table, $select, $order, $sort, $search, $join);
                $recordsFiltered = $this->datatable->countFiltered($table, $select, $order, $sort, $search, $join);
            }

            $result = [
                'draw'              => $this->request->getPost('draw'),
                'recordsTotal'      => $recordTotal,
                'recordsFiltered'   => $recordsFiltered,
                'data'              => $data
            ];

            return $this->response->setJSON($result);
        }
    }
}

<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_AllowanceAtt;
use Config\Services;

class AllowanceAtt extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_AllowanceAtt($this->request);
        $this->entity = new \App\Entities\AllowanceAtt();
    }

    public function reportIndex()
    {
        return $this->template->render('report/allowance/v_rpt_allowance');
    }

    public function reportShowAll()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelect();
            $join = $this->model->getJoin();
            $order = $this->request->getPost('columns');
            $sort = $this->model->order;
            $search = $this->request->getPost('search');

            $data = [];

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join);

            foreach ($list as $value) :
                $row = [];
                $ID = $value->trx_allow_attendance_id;

                $number++;

                $row[] = $number;
                $row[] = $value->documentno;
                $row[] = $value->employee_fullname;
                $row[] = format_dmy($value->submissiondate, '-');
                $row[] = $value->submissiontype;
                $row[] = $value->amount;
                $row[] = $value->reason;
                $data[] = $row;
            endforeach;

            $result = [
                'draw'              => $this->request->getPost('draw'),
                'recordsTotal'      => $this->datatable->countAll($table, $select, $order, $sort, $search, $join),
                'recordsFiltered'   => $this->datatable->countFiltered($table, $select, $order, $sort, $search, $join),
                'data'              => $data
            ];

            return $this->response->setJSON($result);
        }
    }
}

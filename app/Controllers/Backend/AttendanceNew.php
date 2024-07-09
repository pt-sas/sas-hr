<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_AttendanceNew;
use App\Models\M_EmpBranch;
use App\Models\M_EmpDivision;
use Config\Services;

class AttendanceNew extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_AttendanceNew($this->request);
        $this->entity = new \App\Entities\AttendanceNew();
    }

    public function reportIndex()
    {
        $date = format_dmy(date('Y-m-d'), "-");

        $data = [
            'date_range' => $date . ' - ' . $date
        ];
        return $this->template->render('report/attendancenew/v_attendance', $data);
    }

    public function reportShowAll()
    {
        $mEmpBranch = new M_EmpBranch($this->request);
        $mEmpDiv = new M_EmpDivision($this->request);
        $post = $this->request->getVar();
        $data = [];

        $recordTotal = 0;
        $recordsFiltered = 0;

        if ($this->request->getMethod(true) === 'POST') {
            if (isset($post['form']) && $post['clear'] === 'false') {
                $table = $this->model->table;
                $select = $this->model->getSelect();
                $order = $this->request->getPost('columns');
                $join = $this->model->getJoin();
                // $sort = ['nik' => 'ASC', 'date' => 'ASC'];
                $search = $this->request->getPost('search');
                $where = [];

                $number = $this->request->getPost('start');
                $list = array_unique($this->datatable->getDatatables($table, $select, $order, '', $search, $join, $where), SORT_REGULAR);

                foreach ($list as $val) :
                    $row = [];

                    $number++;

                    $row[] = $number;
                    $row[] = $val->nik;
                    $row[] = $val->fullname;
                    $row[] = format_dmy($val->date, "-");
                    $row[] = $val->clock_in ? format_time($val->clock_in) : '';
                    $row[] = $val->clock_out ? format_time($val->clock_out) : '';
                    // $row[] = formatyesno($val->absent);
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
    }
}

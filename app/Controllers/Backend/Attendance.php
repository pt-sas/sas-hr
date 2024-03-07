<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Attendance;
use App\Models\M_EmpBranch;
use App\Models\M_EmpDivision;
use Config\Services;

class Attendance extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Attendance($this->request);
        $this->entity = new \App\Entities\Attendance();
    }

    public function reportIndex()
    {
        $date = format_dmy(date('Y-m-d'), "-");

        $data = [
            'date_range' => $date . ' - ' . $date
        ];
        return $this->template->render('report/attendance/v_attendance', $data);
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
                $sort = ['nik' => 'ASC', 'date' => 'ASC'];
                $search = $this->request->getPost('search');
                $where = [];

                foreach ($post['form'] as $value) {
                    if (!empty($value['value'])) {
                        if ($value['name'] === "submissiondate") {
                            $datetime = urldecode($value['value']);
                            $date = explode(" - ", $datetime);
                            $where = [
                                'trx_attendance.date >=' => date("Y-m-d", strtotime($date[0])),
                                'trx_attendance.date <=' => date("Y-m-d", strtotime($date[1]))
                            ];
                        }

                        if ($value['name'] === "md_division_id") {
                            $arrDiv_id = $value['value'];

                            $listDiv = $mEmpDiv->whereIn("md_division_id", $arrDiv_id)->findAll();
                            $where = [
                                'md_employee.md_employee_id'     => array_column($listDiv, "md_employee_id")
                            ];
                        }

                        if ($value['name'] === "md_branch_id") {
                            $arrBranch_id = $value['value'];

                            $listBranch = $mEmpBranch->whereIn("md_branch_id", $arrBranch_id)->findAll();
                            $where = [
                                'md_employee.md_employee_id'     => array_column($listBranch, "md_employee_id")
                            ];
                        }
                    }
                }



                $number = $this->request->getPost('start');
                $list = array_unique($this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where), SORT_REGULAR);

                foreach ($list as $val) :
                    $row = [];

                    $number++;

                    $row[] = $number;
                    $row[] = $val->nik;
                    $row[] = $val->fullname;
                    $row[] = format_dmy($val->date, "-");
                    $row[] = $val->clock_in ? format_time($val->clock_in) : '';
                    $row[] = $val->clock_out ? format_time($val->clock_out) : '';
                    $row[] = formatyesno($val->absent);
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

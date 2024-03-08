<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use App\Models\M_Attendance;
use App\Models\M_Holiday;
use Config\Services;

class ListAbsent extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Attendance($this->request);
        $this->entity = new \App\Entities\Attendance();
    }

    public function index()
    {
        return $this->template->render('generate/listabsent/v_list_absent');
    }

    public function showAll()
    {
        $mAbsent = new M_Absent($this->request);
        $mHoliday = new M_Holiday($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelect();
            $order = [];
            $join = $this->model->getJoin();
            $sort = ['nik' => 'ASC'];
            $search = [];
            $where = ['trx_attendance.absent' => 'Y'];
            $number = $this->request->getPost('start');
            $list = array_unique($this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where), SORT_REGULAR);

            $data = [];

            foreach ($list as $val) :
                $parAbsent = [
                    'trx_absent.startdate >=' => $val->date,
                    'trx_absent.startdate <=' => date('Y-m-d', strtotime($val->date)) . ' 23:59:59',
                    'trx_absent.docstatus' => 'CO',
                    'trx_absent.md_employee_id' => $val->md_employee_id
                ];

                $absent = $mAbsent->where($parAbsent)->find();

                // Get Date Range From Absent Date
                $holiday = $mHoliday->getHolidayDate();
                $date_range = getDatesFromRange($val->date, date('Y-m-d'), $holiday);
                $totalrange = count($date_range);

                if (empty($absent) && $totalrange > 3) {

                    $row = [];
                    $ID = $val->trx_attendance_id;

                    $number++;

                    $row[] = $number;
                    $row[] = $val->nik;
                    $row[] = $val->fullname;
                    $row[] = format_dmy($val->date, "-");
                    $row[] = $this->template->buttonGenerate($ID);
                    $data[] = $row;
                }

            endforeach;
            $recordTotal = count($data);
            $recordsFiltered = count($data);

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

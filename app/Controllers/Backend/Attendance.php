<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_AssignmentDate;
use App\Models\M_AssignmentDetail;
use App\Models\M_Attendance;
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
        $post = $this->request->getVar();

        $recordTotal = 0;
        $recordsFiltered = 0;
        $data = [];

        if ($this->request->getMethod(true) === 'POST') {
            if (isset($post['form']) && $post['clear'] === 'false') {
                $table = "v_attendance";
                $select = $this->model->getSelect();
                $join = $this->model->getJoin();
                $order = $this->request->getPost('columns');
                $search = $this->request->getPost('search');
                $sort = ['v_attendance.date' => 'ASC', 'v_attendance.nik' => 'ASC'];
                $where = [];

                $number = $this->request->getPost('start');
                $list = array_unique($this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where), SORT_REGULAR);

                foreach ($list as $val) :
                    $row = [];

                    $number++;

                    $row[] = $number;
                    $row[] = $val->nik;
                    $row[] = $val->fullname;
                    $row[] = format_dmy($val->date, "-");
                    $row[] = $val->clock_in ?? format_time($val->clock_in);
                    $row[] = $val->clock_out ?? format_time($val->clock_out);
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

    public function getClockInOut()
    {
        if ($this->request->isAJAX()) {
            $post = $this->request->getVar();

            try {
                $data = '';

                if ($post['typeform'] == 100008) {
                    $mAssignmentDate = new M_AssignmentDate($this->request);
                    $mAssignmentDetail = new M_AssignmentDetail($this->request);

                    $subDetail = $mAssignmentDate->find($post['id']);
                    $detail = $mAssignmentDetail->find($subDetail->{$mAssignmentDetail->primaryKey});

                    $att = $this->model->getAttendanceBranch([
                        'v_attendance_serialnumber.md_employee_id' => $detail->md_employee_id,
                        'v_attendance_serialnumber.date' => date("Y-m-d", strtotime($subDetail->date)),
                        'md_attendance_machines.md_branch_id' => $post['md_branch_id']
                    ])->getRow();

                    $data = [
                        'clock_in' => $att && $att->clock_in ? format_time($att->clock_in) : '',
                        'clock_out' => $att && $att->clock_out ? format_time($att->clock_out) : ''
                    ];
                } else {
                    $att = $this->model->getAttendance([
                        'v_attendance.nik'        => $post['nik'],
                        'v_attendance.date'       => date("Y-m-d", strtotime($post['startdate']))
                    ])->getRow();

                    if ($post['typeform'] == 100012 && $att) {
                        $data = format_time($att->clock_in);
                    } else if ($post['typeform'] == 100013 && $att) {
                        $data = format_time($att->clock_out);
                    }
                }

                $response['clock'] = $data;
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}

<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use App\Models\M_AbsentDetail;
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
        $start_date = format_dmy(date('Y-m-d', strtotime('- 1 days')), "-");
        $end_date = format_dmy(date('Y-m-d'), "-");

        $data = [
            'date_range'            => $start_date . ' - ' . $end_date,
            'toolbarRealization'    => $this->template->buttonGenerate()
        ];

        return $this->template->render('generate/listabsent/v_list_absent', $data);
    }

    public function showAll()
    {
        $mAbsent = new M_Absent($this->request);
        $mAbsentDetail = new M_AbsentDetail($this->request);
        $mHoliday = new M_Holiday($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelect();
            $join = $this->model->getJoin();
            $order = $this->request->getPost('columns');
            $search = $this->request->getPost('search');
            $sort = ['date' => 'ASC', 'nik' => 'ASC'];

            $where = ['trx_attendance.absent' => 'N'];

            $data = [];

            $number = $this->request->getPost('start');
            $list = array_unique($this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where), SORT_REGULAR);

            foreach ($list as $val) :
                $parAbsent = [
                    'trx_absent_detail.date' => $val->date,
                    'trx_absent.docstatus' => 'CO',
                    'trx_absent.md_employee_id' => $val->md_employee_id,
                    'trx_absent_detail.isagree' => 'Y'
                ];

                $absent = $mAbsentDetail->getAbsentDetail($parAbsent)->getResult();

                // Get Date Range From Absent Date
                $holiday = $mHoliday->getHolidayDate();
                $date_range = getDatesFromRange($val->date, date('Y-m-d'), $holiday);
                $totalrange = count($date_range);

                $fieldChk = new \App\Entities\Table();
                $fieldChk->setName("ischecked");
                $fieldChk->setType("checkbox");
                $fieldChk->setClass("check-alpa");

                if (empty($absent) && $totalrange > 3) {

                    $row = [];
                    $ID = $val->trx_attendance_id;
                    $fieldChk->setValue($ID);

                    $number++;
                    $row[] = $this->field->fieldTable($fieldChk);
                    $row[] = $val->nik;
                    $row[] = $val->fullname;
                    $row[] = format_dmy($val->date, "-");
                    $row[] = $val->description;
                    $row[] = $this->template->buttonEdit($ID);
                    $data[] = $row;
                }

            endforeach;
            $recordTotal = count($data);
            $recordsFiltered = count($data);

            $result = [
                'draw' => $this->request->getPost('draw'),
                'recordsTotal' => $recordTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data
            ];

            return $this->response->setJSON($result);
        }
    }

    public function create()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            try {
                if (!$this->validation->run($post, 'attendance')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else {
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
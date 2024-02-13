<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_AllowanceAtt;
use App\Models\M_Employee;
use App\Models\M_Holiday;
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
        $date = format_dmy(date('Y-m-d'), "-");

        $data = [
            'date_range' => $date . ' - ' . $date
        ];

        return $this->template->render('report/allowance/v_rpt_allowance', $data);
    }

    public function reportShowAll()
    {
        $mHoliday = new M_Holiday($this->request);
        $mEmployee = new M_Employee($this->request);
        $post = $this->request->getVar();
        $data = [];

        $recordTotal = 0;
        $recordsFiltered = 0;
        $date1 = [];

        if ($this->request->getMethod(true) === 'POST') {
            if (isset($post['form']) && $post['clear'] === 'false') {
                $table = $mEmployee->table;
                $select = $mEmployee->findAll();
                $order = $this->request->getPost('columns');
                $sort = ['fullname' => 'ASC'];
                $search = $this->request->getPost('search');
                $where['md_employee.isactive'] = 'Y';

                foreach ($post['form'] as $value) {
                    if ($value['name'] === "submissiondate") {
                        $datetime =  urldecode($value['value']);
                        $date = explode(" - ", $datetime);
                    }
                }

                $start_date = date("Y-m-d", strtotime($date[0]));
                $end_date = date("Y-m-d", strtotime($date[1]));
                $holiday = $mHoliday->getHolidayDate();

                $date_range = getDatesFromRange($start_date, $end_date, $holiday);

                $number = $this->request->getPost('start');
                $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, [], $where);

                foreach ($date_range as $value) :
                    foreach ($list as $val) :
                        $row = [];

                        $parAllow = [
                            'trx_allow_attendance.md_employee_id'    => $val->md_employee_id,
                            'trx_allow_attendance.submissiondate'    => $value
                        ];

                        $allow = $this->model->getAllowance($parAllow)->getRow();

                        $number++;
                        $qty = 1;

                        $row[] = $number;
                        $row[] = $allow ? $allow->documentno : "";
                        $row[] = $val->fullname;
                        $row[] = format_dmy($value, "-");
                        $row[] = $allow ? $allow->submissiontype : "";
                        $row[] = $allow ? ($qty - $allow->amount) : $qty;
                        $row[] = $allow ? $allow->reason : "";
                        $data[] = $row;
                    endforeach;
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

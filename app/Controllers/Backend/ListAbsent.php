<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_AbsentDetail;
use App\Models\M_Attendance;
use App\Models\M_EmpWorkDay;
use App\Models\M_Holiday;
use App\Models\M_WorkDetail;
use App\Models\M_Employee;
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
            'toolbarListAbsent'     => $this->template->buttonGenerate()
        ];

        return $this->template->render('generate/listabsent/v_list_absent', $data);
    }

    public function showAll()
    {
        $mAbsentDetail = new M_AbsentDetail($this->request);
        $mHoliday = new M_Holiday($this->request);
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);
        $mEmployee = new M_Employee($this->request);
        $mAttendance = new M_Attendance($this->request);
        $post = $this->request->getVar();

        if ($this->request->getMethod(true) === 'POST') {
            $table = $mEmployee->table;
            $select = $mEmployee->findAll();
            $order = $this->request->getPost('columns');
            $search = $this->request->getPost('search');
            $sort = ['nik' => 'ASC', 'fullname' => 'ASC'];

            $where = [
                'isactive'          => 'Y',
                'md_status_id <>'   => 100003 //Resign
            ];

            $data = [];

            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, [], $where);

            $number = $this->request->getPost('start');
            foreach ($post['form'] as $value) :
                if ($value['name'] === "date") {
                    $holiday = $mHoliday->getHolidayDate();
                    $today = date('Y-m-d');

                    if (!empty($value['value'])) {
                        $datetime = urldecode($value['value']);
                        $date = explode(" - ", $datetime);
                    } else {
                        // $date[0] = date('Y-m-d', strtotime('first day of january this year'));
                        $date[0] = date('Y-m-d', strtotime('-7 day'));
                        $date[1] = $today;
                    }

                    $date_range = getDatesFromRange($date[0], $date[1], [], 'Y-m-d H:i:s', 'all');

                    foreach ($date_range as $date) :
                        foreach ($list as $val) :
                            //TODO : Get work day employee
                            $workDay = $mEmpWork->where([
                                'md_employee_id'    => $val->md_employee_id,
                                'validfrom <='      => $today
                            ])->orderBy('validfrom', 'ASC')->first();

                            if ($workDay) {
                                // $parSub = [
                                //     'v_realization.date' => $date,
                                //     'v_realization.md_employee_id' => $val->md_employee_id,
                                //     'v_realization.isagree' => 'Y'
                                // ];

                                // $submission = $mAbsentDetail->getAbsentDetail($parSub)->getResult();

                                $parAbsent = "DATE_FORMAT(v_realization.date, '%Y-%m-%d') = '{$date}'
                                              AND v_realization.md_employee_id = {$val->md_employee_id}
                                              AND v_realization.isagree = 'Y'";

                                $submission = $mAbsentDetail->getAllSubmission($parAbsent)->getResult();


                                //TODO : Get Work Detail
                                $whereClause = "md_work_detail.isactive = 'Y'";
                                $whereClause .= " AND md_employee_work.md_employee_id = {$val->md_employee_id}";
                                $whereClause .= " AND md_work.md_work_id = {$workDay->md_work_id}";
                                $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getResult();

                                $daysOff = getDaysOff($workDetail);

                                $numDate = date('w', strtotime($date));
                                $date = date('Y-m-d', strtotime($date));

                                $attend = $mAttendance->getAttendance([
                                    'v_attendance.nik'        => $val->nik,
                                    'v_attendance.date'       => $date
                                ])->getRow();

                                $todayRange = getDatesFromRange($date, $today, $holiday, 'Y-m-d H:i:s', 'all', $daysOff);
                                $totalrange = count($todayRange);

                                $fieldChk = new \App\Entities\Table();
                                $fieldChk->setName("ischecked");
                                $fieldChk->setType("checkbox");
                                $fieldChk->setClass("check-alpa");

                                if (
                                    empty($submission) &&
                                    !in_array($numDate, $daysOff) &&
                                    !in_array($date, $holiday) &&
                                    empty($attend) &&
                                    $totalrange > 3
                                ) {
                                    $row = [];
                                    $ID = $val->md_employee_id;
                                    $number++;

                                    $fieldChk->setValue($ID);
                                    $row[] = $this->field->fieldTable($fieldChk);
                                    $row[] = $val->nik;
                                    $row[] = $val->fullname;
                                    $row[] = format_dmy($date, "-");
                                    //         $row[] = $val->description;
                                    //         $row[] = $this->template->buttonEdit($ID);
                                    $data[] = $row;
                                }
                            }
                        endforeach;
                    endforeach;
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

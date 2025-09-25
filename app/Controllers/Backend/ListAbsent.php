<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use App\Models\M_AbsentDetail;
use App\Models\M_Attendance;
use App\Models\M_EmpWorkDay;
use App\Models\M_Holiday;
use App\Models\M_WorkDetail;
use App\Models\M_Employee;
use App\Models\M_Rule;
use App\Models\M_RuleDetail;
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
        if ($this->request->getMethod(true) === 'POST') {
            $mEmployee = new M_Employee($this->request);
            $mHoliday = new M_Holiday($this->request);
            $mWorkDetail = new M_WorkDetail($this->request);
            $mAttendance = new M_Attendance($this->request);
            $mEmpWork = new M_EmpWorkDay($this->request);
            $mAbsent = new M_Absent($this->request);
            $mRule = new M_Rule($this->request);
            $mRuleDetail = new M_RuleDetail($this->request);

            $post = $this->request->getVar();
            $today = date('Y-m-d');
            $holiday = $mHoliday->getHolidayDate();

            $rule = $mRule->where('name', 'List Alpa')->first();
            $ruleDetail = $mRuleDetail->where(['name' => 'Included Level', 'md_rule_id' => $rule->md_rule_id])->first();
            $operation = getOperation($ruleDetail->operation);

            $table = $mEmployee->table;
            $select = "*";
            $order = [];
            $search = [];
            $sort = ['nik' => 'ASC'];

            $where["isactive"] = 'Y';
            $where["md_status_id"] = ["value" => [$this->Status_PERMANENT, $this->Status_PROBATION, $this->Status_KONTRAK]];
            $where["md_levelling_id {$operation}"] = $ruleDetail->condition;

            foreach ($post['form'] as $value) :
                if ($value['name'] === "date") {
                    if (!empty($value['value'])) {
                        $dateRange = explode(" - ",  urldecode($value['value']));
                    } else {
                        $dateRange = [date('Y-m-d', strtotime('-7 day')), $today];
                    }
                }

            endforeach;

            $date_range = getDatesFromRange($dateRange[0], $dateRange[1], $holiday, 'Y-m-d H:i:s', 'all');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, [], $where);

            // TODO : Preparing data before looping
            // Get All Submission and Stored in array
            $dateStart = date('Y-m-d', strtotime($dateRange[0]));
            $dateEnd = date('Y-m-d', strtotime($dateRange[1]));
            $whereClause = "DATE(v_all_submission.date) BETWEEN '{$dateStart}' AND '{$dateEnd}'";
            $whereClause .= " AND v_all_submission.isagree IN ('{$this->LINESTATUS_Disetujui}', '{$this->LINESTATUS_Approval}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Realisasi_HRD}')";
            $whereClause .= " AND v_all_submission.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')";
            $whereClause .= " AND v_all_submission.submissiontype IN (" . implode(", ", array_merge($this->Form_Satu_Hari, $this->Form_Setengah_Hari)) . ")";
            $allSubmission = [];

            foreach ($mAbsent->getAllSubmission($whereClause)->getResult() as $val) {
                $dateKey = date('Y-m-d', strtotime($val->date));
                $allSubmission[$val->md_employee_id][$dateKey] = $val;
            }

            // Get All Attendance and Stored in array
            $whereClause = "DATE(v_attendance.date) BETWEEN '{$dateStart}' AND '{$dateEnd}'";
            $allAttendance = [];
            foreach ($mAttendance->getAttendance($whereClause)->getResult() as $val) {
                $dateKey = date('Y-m-d', strtotime($val->date));
                $allAttendance[$val->md_employee_id][$dateKey] = $val;
            }

            $data = [];
            $number = $this->request->getPost('start');
            foreach ($list as $emp) {
                $workDay = $mEmpWork->where([
                    'md_employee_id'    => $emp->md_employee_id,
                    'validfrom <='      => $dateStart,
                    'validto >='        => $dateEnd
                ])->orderBy('validfrom', 'ASC')->first();

                if (!$workDay) continue;

                //TODO : Get Work Detail
                $whereClause = "md_work_detail.isactive = 'Y'";
                $whereClause .= " AND md_employee_work.md_employee_id = {$emp->md_employee_id}";
                $whereClause .= " AND md_work.md_work_id = {$workDay->md_work_id}";
                $workDetail = $mWorkDetail->getWorkDetail($whereClause)->getResult();

                $daysOff = getDaysOff($workDetail);

                foreach ($date_range as $date) {
                    $date = date('Y-m-d', strtotime($date));

                    $numDate = date('w', strtotime($date));
                    if (in_array($numDate, $daysOff)) continue;

                    if (in_array($date, $holiday)) continue;

                    $dateInterval = getDatesFromRange($date, $today, $holiday, 'Y-m-d H:i:s', 'all', $daysOff);
                    $totalInterval = count($dateInterval);

                    $fieldChk = new \App\Entities\Table();
                    $fieldChk->setName("ischecked");
                    $fieldChk->setType("checkbox");
                    $fieldChk->setClass("check-alpa");

                    if (
                        !isset($allSubmission[$emp->md_employee_id][$date]) &&
                        !isset($allAttendance[$emp->md_employee_id][$date]) &&
                        $totalInterval > $rule->max
                    ) {
                        $row = [];
                        $ID = $emp->md_employee_id;
                        $number++;

                        $fieldChk->setValue($ID);
                        $row[] = $this->field->fieldTable($fieldChk);
                        $row[] = $emp->nik;
                        $row[] = $emp->fullname;
                        $row[] = format_dmy($date, "-");
                        $data[] = $row;
                    }
                }
            }

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
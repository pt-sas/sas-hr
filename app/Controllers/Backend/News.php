<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Absent;
use App\Models\M_Attendance;
use App\Models\M_EmpBranch;
use App\Models\M_EmpDivision;
use App\Models\M_EmpWorkDay;
use App\Models\M_Holiday;
use App\Models\M_WorkDetail;
use App\Models\M_Employee;
use App\Models\M_News;
use App\Models\M_Rule;
use App\Models\M_RuleDetail;
use Config\Services;

class News extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_News($this->request);
        $this->entity = new \App\Entities\News();
    }

    public function index()
    {
        $start_date = format_dmy(date('Y-m-d'), "-");
        $end_date = format_dmy(date('Y-m-d'), "-");

        $data = [
            'date_range'            => $start_date . ' - ' . $end_date
        ];

        return $this->template->render('generate/listnews/v_list_news', $data);
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
            $mEmpBranch = new M_EmpBranch($this->request);
            $mEmpDiv = new M_EmpDivision($this->request);

            $post = $this->request->getVar();
            $today = date('Y-m-d');
            $time = date('H:i:s');

            //* Get Holiday
            $holiday = $mHoliday->getHolidayDate();

            //* Get Employee List
            $empList = $this->access->getEmployeeData(true, true);
            $empListSql = implode(', ', $empList);

            //* Get Rule & Operation for Level
            $rule = $mRule->where('name', 'List Alpa')->first();
            $ruleDetail = $mRuleDetail->where(['name' => 'Included Level', 'md_rule_id' => $rule->md_rule_id])->first();
            $operation = getOperation($ruleDetail->operation);

            //* Get Employee List
            $table = $mEmployee->table;
            $select = "distinct(md_employee.md_employee_id), md_employee.nik, md_employee.fullname, md_employee.md_levelling_id, md_levelling.name as jabatan";
            $order = [];
            $search = [];
            $join = [
                ['tableJoin' => 'md_employee_branch', 'columnJoin' => 'md_employee.md_employee_id = md_employee_branch.md_employee_id', 'typeJoin' => 'left'],
                ['tableJoin' => 'md_employee_division', 'columnJoin' => 'md_employee.md_employee_id = md_employee_division.md_employee_id', 'typeJoin' => 'left'],
                ['tableJoin' => 'md_levelling', 'columnJoin' => 'md_employee.md_levelling_id = md_levelling.md_levelling_id', 'typeJoin' => 'left']
            ];

            $sort = ['md_employee.md_levelling_id' => 'ASC', 'nik' => 'ASC'];

            $where["md_employee.isactive"] = 'Y';
            $where["md_employee.md_status_id"] = ["value" => [$this->Status_PERMANENT, $this->Status_PROBATION, $this->Status_KONTRAK]];
            $where["md_employee.md_levelling_id {$operation}"] = $ruleDetail->condition;
            $where["md_employee.md_employee_id"] = ["value" => $empList];

            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            //* Settle Up Parameter
            foreach ($post['form'] as $value) :
                if ($value['name'] === "date") {
                    if (!empty($value['value'])) {
                        $dateRange = explode(" - ",  urldecode($value['value']));
                    } else {
                        $dateRange = [date('Y-m-d', strtotime('first day of this month')), $today];
                    }
                }

                if ($value['name'] === "kabar") {
                    if (!empty($value['value'])) {
                        $filterKabar = $value['value'];
                    }
                }

            endforeach;

            $date_range = getDatesFromRange($dateRange[0], $dateRange[1], $holiday, 'Y-m-d H:i:s', 'all');

            //* Get All Emp Workday
            $allEmpWork = [];
            foreach ($mEmpWork->whereIn('md_employee_id', $empList)->findAll() as $val) {
                $allEmpWork[$val->md_employee_id][$val->md_employee_work_id] = $val;
            }

            //* Get All Work Detail — indexed by [work_id][day_id] for O(1) lookup, plus a flat per-work_id list retained for getDaysOff().
            $workDetailByDay = [];
            $workDetailByWork = [];
            foreach ($mWorkDetail->getWorkDetail("md_work_detail.isactive = 'Y'")->getResult() as $val) {
                $workDetailByDay[$val->md_work_id][$val->md_day_id] = $val;
                $workDetailByWork[$val->md_work_id][$val->md_work_detail_id] = $val;
            }

            $daysOffCache = [];

            //* Get All Submission
            $dateStart = date('Y-m-d', strtotime($dateRange[0]));
            $dateEnd = date('Y-m-d', strtotime($dateRange[1]));
            $whereClause = "DATE(v_all_submission.date) BETWEEN '{$dateStart}' AND '{$dateEnd}'";
            $whereClause .= " AND v_all_submission.isagree IN ('{$this->LINESTATUS_Disetujui}', '{$this->LINESTATUS_Approval}', '{$this->LINESTATUS_Realisasi_Atasan}', '{$this->LINESTATUS_Realisasi_HRD}')";
            $whereClause .= " AND v_all_submission.docstatus IN ('{$this->DOCSTATUS_Completed}', '{$this->DOCSTATUS_Inprogress}')";
            $whereClause .= " AND v_all_submission.submissiontype IN (100003, 100007, 100009)";
            $whereClause .= " AND v_all_submission.md_employee_id IN ({$empListSql})";
            $allSubmission = [];

            foreach ($mAbsent->getAllSubmission($whereClause)->getResult() as $val) {
                $dateKey = date('Y-m-d', strtotime($val->date));
                $allSubmission[$val->md_employee_id][$dateKey] = $val;
            }

            //* Get All Attendance and Stored in array
            $whereClause = "DATE(v_attendance.date) BETWEEN '{$dateStart}' AND '{$dateEnd}'";
            $whereClause .= " AND v_attendance.md_employee_id IN ({$empListSql})";
            $allAttendance = [];
            foreach ($mAttendance->getAttendance($whereClause)->getResult() as $val) {
                $dateKey = date('Y-m-d', strtotime($val->date));
                $allAttendance[$val->md_employee_id][$dateKey] = $val;
            }

            //* Get All News
            $whereClause = "DATE(date) BETWEEN '{$dateStart}' AND '{$dateEnd}'";
            $whereClause .= " AND md_employee_id IN ({$empListSql})";
            $allNews = [];
            foreach ($this->model->where($whereClause)->findAll() as $val) {
                $dateKey = date('Y-m-d', strtotime($val->date));
                $allNews[$val->md_employee_id][$dateKey] = $val;
            }

            //* Get All Employee Branch & Division
            $whereClause = "md_employee_id IN ({$empListSql})";
            $allEmpBranch = [];
            foreach ($mEmpBranch->getBranchDetail($whereClause)->getResult() as $val) {
                $employeeId = $val->md_employee_id;
                $allEmpBranch[$employeeId][] = $val->branch_name;
            }
            //* Concat if employee have a multiple branch into a single string
            foreach ($allEmpBranch as $employeeId => $branches) {
                $allEmpBranch[$employeeId] = implode(', ', array_unique($branches));
            }

            $allEmpDivision = [];
            foreach ($mEmpDiv->getDivisionDetail($whereClause)->getResult() as $val) {
                $employeeId = $val->md_employee_id;

                $allEmpDivision[$employeeId][] = $val->division_name;
            }
            //* Concat if employee have a multiple division into a single string
            foreach ($allEmpDivision as $employeeId => $divisions) {
                $allEmpDivision[$employeeId] = implode(', ', array_unique($divisions));
            }

            $data = [];
            $number = $this->request->getPost('start');
            foreach ($list as $emp) {
                //* Get Work Days
                $empWorkDay = isset($allEmpWork[$emp->md_employee_id]) ? $allEmpWork[$emp->md_employee_id] : null;
                if (!$empWorkDay) continue;

                foreach ($date_range as $date) {
                    $date = date('Y-m-d', strtotime($date));
                    $day = date('N', strtotime($date));

                    $workDay = null;
                    foreach ($empWorkDay as $work) {
                        if ($work->validfrom <= $date && $work->validto >= $date) {
                            $workDay = $work;
                            break;
                        }
                    }

                    if (!$workDay) continue;

                    $workId = $workDay->md_work_id;

                    $dayDetail = $workDetailByDay[$workId][$day] ?? null;

                    if (!$dayDetail) continue;

                    $workHour = $dayDetail->startwork;

                    if ($date == $today && $time < $workHour) continue;

                    if (!isset($daysOffCache[$workId])) {
                        $daysOffCache[$workId] = getDaysOff($workDetailByWork[$workId] ?? []);
                    }

                    $daysOff = $daysOffCache[$workId];

                    $numDate = date('w', strtotime($date));
                    if (in_array($numDate, $daysOff)) continue;

                    if (in_array($date, $holiday)) continue;

                    if (
                        !isset($allSubmission[$emp->md_employee_id][$date]) &&
                        !isset($allAttendance[$emp->md_employee_id][$date])
                    ) {
                        $news = isset($allNews[$emp->md_employee_id][$date]) ? ($allNews[$emp->md_employee_id][$date]) : null;

                        if (!empty($filterKabar)) {
                            if ($filterKabar == "Y" && empty($news))
                                continue;

                            if ($filterKabar == "N" && !empty($news))
                                continue;
                        }

                        $row = [];
                        $ID = $emp->md_employee_id;
                        $number++;

                        $row[] = $number;
                        $row[] = $emp->nik;
                        $row[] = $emp->fullname;
                        $row[] = isset($allEmpBranch[$emp->md_employee_id]) ? $allEmpBranch[$emp->md_employee_id] : '';
                        $row[] = isset($allEmpDivision[$emp->md_employee_id]) ? $allEmpDivision[$emp->md_employee_id] : '';
                        $row[] = $emp->jabatan;
                        $row[] = format_dmy($date, "-");
                        $row[] = !empty($news) ? $news->reason : '';
                        $row[] = $this->template->buttonNews($ID);
                        $data[] = $row;
                    }
                }
            }

            $recordTotal = count($data);

            $result = [
                'draw' => $this->request->getPost('draw'),
                'recordsTotal' => $recordTotal,
                'recordsFiltered' => $recordTotal,
                'data' => $data
            ];

            return $this->response->setJSON($result);
        }
    }

    public function create()
    {
        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();
            $md_employee_id = $post['md_employee_id'];
            $date = date('Y-m-d', strtotime($post['date']));
            $reason = $post['reason'];
            $today = date('Y-m-d');
            $maxTime = convertToMinutes("17:00");
            $nowTime = convertToMinutes(date('H:m'));

            try {
                if (!$this->validation->run($post, 'news')) {
                    $response = $this->field->errorValidation($this->model->table, $post);
                } else if ($nowTime > $maxTime || $today > $date) {
                    $response = message('success', false, 'Tidak bisa menginput kabar karena sudah lewat batas penginputan');
                } else {
                    $trxNews = $this->model->where(['md_employee_id' => $md_employee_id, 'DATE(date)' => $date])->first();
                    if ($trxNews && !empty($reason)) {
                        $this->entity->trx_news_id = $trxNews->trx_news_id;
                        $this->entity->reason = $reason;
                        $response = $this->save();
                    } else if (!$trxNews && !empty($reason)) {
                        $this->entity->md_employee_id = $md_employee_id;
                        $this->entity->date = $date;
                        $this->entity->reason = $reason;
                        $response = $this->save();
                    } else if ($trxNews && empty($reason)) {
                        $result = $this->delete($trxNews->trx_news_id);
                        if ($result) {
                            $response = message('success', true, 'Kabar berhasil dihapus');
                        }
                    } else {
                        $response = message('success', true, 'Tidak ada perubahan data');
                    }
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}

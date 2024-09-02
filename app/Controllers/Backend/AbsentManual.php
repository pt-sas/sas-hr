<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Attendance;
use App\Models\M_Employee;
use App\Models\M_EmpWorkDay;
use App\Models\M_WorkDetail;
use App\Models\M_RuleDetail;
use App\Models\M_AllowanceAtt;
use App\Models\M_Absent;
use CodeIgniter\Config\Services;

class AbsentManual extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Attendance($this->request);
        $this->entity = new \App\Entities\Attendance();
    }

    public function index()
    {
        $data = [
            'timestamp' => strtotime(date('Y-m-d H:i:s'))
        ];

        return $this->template->render('transaction/absentmanual/v_absent_manual', $data);
    }

    public function showAll()
    {
        $post = $this->request->getVar();

        $recordTotal = 0;
        $recordsFiltered = 0;
        $data = [];
        $today = date('Y-m-d');

        if ($this->request->getMethod(true) === 'POST') {
            $table = $this->model->table;
            $select = $this->model->getSelectDetail();
            $join = $this->model->getJoinDetail();
            $order = $this->request->getPost('columns');
            $search = $this->request->getPost('search');
            $sort = ['checktime' => 'ASC', 'nik' => 'ASC'];

            $where['date_format(checktime, "%Y-%m-%d")'] = $today;
            $where[$table . '.created_by'] = $this->access->getSessionUser();
            $where['serialnumber'] = "";

            $number = $this->request->getPost('start');
            $list = $this->datatable->getDatatables($table, $select, $order, $sort, $search, $join, $where);

            foreach ($list as $val) :
                $row = [];

                $number++;

                $row[] = $number;
                $row[] = $val->nik;
                $row[] = $val->fullname;
                $row[] = format_dmy($val->checktime, "-");
                $row[] = format_time($val->checktime, "-");
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

    public function create()
    {
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);
        $mEmployee = new M_Employee($this->request);
        $mAllowance = new M_AllowanceAtt($this->request);
        $mAbsent = new M_Absent($this->request);

        if ($this->request->getMethod(true) === 'POST') {
            $post = $this->request->getVar();

            $nik = $post['nik'];
            $today = date('Y-m-d');
            $day = date('w');
            $workHour = convertToMinutes("08:00");
            $breakTime = "12:00";
            $amount = 0;
            $formType = 0;

            try {
                $rowEmp = $mEmployee->where('nik', $nik)->first();

                if ($rowEmp) {
                    $this->entity->nik = $nik;
                    $this->entity->checktime = $post['checktime'];

                    $response = $this->save();

                    //TODO : Get work day employee
                    $workDay = $mEmpWork->where([
                        'md_employee_id'    => $rowEmp->md_employee_id,
                        'validfrom <='      => $today
                    ])->orderBy('validfrom', 'ASC')->first();

                    $attToday = $this->model->getAttendance([
                        'v_attendance.nik'        => $nik,
                        'v_attendance.date'       => date("Y-m-d", strtotime($post['checktime']))
                    ])->getRow();

                    //TODO: Masukan data allowance hari sebelumnya jika tidak ada absen pulang 
                    if ($attToday) {
                        $day = date('w', strtotime($attToday->date));
                        $day = strtoupper(formatDay_idn($day));

                        $whereClause = "md_work_detail.isactive = 'Y'";
                        $whereClause .= " AND md_employee_work.md_employee_id = $rowEmp->md_employee_id";
                        $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
                        $whereClause .= " AND md_day.name = '$day'";
                        $work = $mWorkDetail->getWorkDetail($whereClause)->getRow();

                        if ($work) {
                            $clockIn = $attToday->clock_in;
                            $clockOut = $attToday->clock_out;
                            $workInTime = convertToMinutes($clockIn);
                            $workOutTime = convertToMinutes($clockOut);
                            $workHour = convertToMinutes($work->startwork);
                            $endBreakHour = convertToMinutes($work->breakend);
                            $checkTime = date("Y-m-d H:i:s");

                            $ruleDetail = $mRuleDetail->where([
                                "isactive"  => "Y",
                                "name <>"   => null
                            ])->findAll();

                            foreach ($ruleDetail as $detail) {
                                if (
                                    $detail->name === "Terlambat 1/2 Hari"
                                    && getOperationResult($workInTime, ($workHour + $detail->condition), $detail->operation)
                                    && !empty($clockIn)
                                ) {
                                    $checkTime = $clockIn;
                                    $dateTime = date("Y-m-d", strtotime($post['checktime'])) . " " . $checkTime;

                                    $allowIn = $mAllowance->where([
                                        'md_employee_id'    => $rowEmp->md_employee_id,
                                        'submissiondate'    => $dateTime,
                                        'submissiontype'    => $mAbsent->Pengajuan_Datang_Terlambat,
                                        'table'             => $this->model->table
                                    ])->first();

                                    if (is_null($allowIn)) {
                                        $amount = $detail->value;
                                        $formType = $mAbsent->Pengajuan_Datang_Terlambat;
                                    }
                                }

                                if (
                                    $detail->name === "Terlambat"
                                    && getOperationResult($workOutTime, ($workHour + $detail->condition), $detail->operation)
                                    && empty($clockIn) && !empty($clockOut)
                                ) {
                                    $checkTime = $clockOut;
                                    $dateTime = date("Y-m-d", strtotime($rowEmp['checktime'])) . " " . $checkTime;

                                    $allowOut = $mAllowance->where([
                                        'md_employee_id'    => $rowEmp->md_employee_id,
                                        'submissiondate <'  => $dateTime,
                                        'submissiontype'    => $mAbsent->Pengajuan_Datang_Terlambat,
                                        'table'             => $this->model->table
                                    ])->first();

                                    if (is_null($allowOut)) {
                                        $amount = $detail->value;
                                        $formType = $mAbsent->Pengajuan_Datang_Terlambat;
                                    }
                                }

                                if (
                                    $detail->name === "Pulang Cepat 1/2 Hari"
                                    && getOperationResult($workOutTime, ($workHour + $detail->condition), $detail->operation)
                                    && !empty($clockIn) && !empty($clockOut)
                                ) {
                                    $checkTime = $clockOut;
                                    $dateTime = date("Y-m-d", strtotime($post['checktime'])) . " " . $checkTime;

                                    $allowOut = $mAllowance->where([
                                        'md_employee_id'    => $rowEmp->md_employee_id,
                                        'submissiondate <'  => $dateTime,
                                        'submissiontype'    => $mAbsent->Pengajuan_Pulang_Cepat,
                                        'table'             => $this->model->table
                                    ])->first();

                                    if (is_null($allowOut)) {
                                        $amount = $detail->value;
                                        $formType = $mAbsent->Pengajuan_Pulang_Cepat;
                                    }
                                }
                            }

                            $allowSub = $mAllowance->where([
                                'md_employee_id'    => $rowEmp->md_employee_id,
                                'submissiondate'    => date("Y-m-d", strtotime($checkTime)),
                                'table'             => $this->model->table
                            ])->whereIn(
                                'submissiontype',
                                [$mAbsent->Pengajuan_Tugas_Khusus, $mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari]
                            )->first();

                            if ($amount != 0 && is_null($allowSub)) {
                                $entity = new \App\Entities\AllowanceAtt();

                                $entity->table = $this->model->table;
                                $entity->submissiontype = $formType;
                                $entity->submissiondate = date("Y-m-d", strtotime($post['checktime']));
                                $entity->md_employee_id = $rowEmp->md_employee_id;
                                $entity->amount = $amount;
                                $entity->created_by = $this->access->getSessionUser();
                                $entity->updated_by = $this->access->getSessionUser();

                                $mAllowance->save($entity);
                            }
                        }
                    }
                } else {
                    $response = message('success', false, 'Nik tidak terdaftar');
                }
            } catch (\Exception $e) {
                $response = message('error', false, $e->getMessage());
            }

            return $this->response->setJSON($response);
        }
    }
}

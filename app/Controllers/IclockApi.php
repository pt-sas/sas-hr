<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\M_Attend;
use App\Models\M_AllowanceAtt;
use App\Models\M_Employee;
use App\Models\M_EmpWorkDay;
use App\Models\M_WorkDetail;
use App\Models\M_RuleDetail;
use Config\Services;

class IclockApi extends ResourceController
{
    protected $request;
    protected $model;
    protected $helpers = ['action_helper', 'url', 'date_helper'];

    public function __construct()
    {
        $this->request = Services::request();
        $this->model = new M_Attend($this->request);
    }

    public function handshake()
    {
        $get = $this->request->getGet();

        $textResponse = <<<STR
        GET OPTION FROM: {$get['SN']}
        ATTLOGStamp=None
        OPERLOGStamp=9999
        ATTPHOTOStamp=None
        ErrorDelay=30
        Delay=10
        TransTimes=00:00;14: 05
        Transinterval=1
        TransFlag=1111000000
        TimeZone=7
        Realtime=1
        Encrypt=None
    STR;

        return $this->respond($textResponse, 200)
            ->setHeader('Content-Type', 'text/plain');
    }

    public function receive()
    {
        $mEmployee = new M_Employee($this->request);
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);
        $mAttend = new M_Attend($this->request);
        $mAllowance = new M_AllowanceAtt($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);

        $get = $this->request->getGet();

        $today = date('Y-m-d');
        $day = date('w');
        $entryTime = "08:00";
        $breakTime = "12:00";
        $amount = 0;

        try {
            if ($get['table'] === "ATTLOG") {
                $content = $this->request->getBody();
                $arr = preg_split('/\\r\\n|\\r|,|\\n/', $content);
                $jml = count($arr);

                $data = [];
                foreach ($arr as $key => $rey) {
                    $row = [];
                    $req = preg_split('/\\t\\n|\\t|,|\\n/', $rey);

                    $row['nik'] = $req[0];
                    $row['checktime'] = $req[1];
                    $row['status'] = $req[2];
                    $row['verify'] = $req[3];
                    $row['reserved'] = $req[4];
                    $row['reserved2'] = $req[5];
                    $row['serialnumber'] = $get['SN'];
                    $data[] = $row;
                }

                $result = $mAttend->builder->insertBatch($data);

                if ($result > 0) {
                    foreach ($data as $val) {
                        $list = $mEmployee->where("nik", $val['nik'])->first();

                        $att = $mAttend->where([
                            'nik'           => $val['nik'],
                            'checktime'     => $val['checktime'],
                        ])->first();

                        if ($list) {
                            //TODO : Get work day employee
                            $workDay = $mEmpWork->where([
                                'md_employee_id'    => $list->md_employee_id,
                                'validfrom <='      => $today
                            ])->orderBy('validfrom', 'ASC')->first();

                            if (is_null($workDay)) {
                                $workHour = convertToMinutes($entryTime);
                                $startBreakHour = $breakTime;
                            } else {
                                $day = strtoupper(formatDay_idn($day));

                                //TODO: Get Work Detail by day 
                                $work = null;

                                $whereClause = "md_work_detail.isactive = 'Y'";
                                $whereClause .= " AND md_employee_work.md_employee_id = $list->md_employee_id";
                                $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
                                $whereClause .= " AND md_day.name = '$day'";
                                $work = $mWorkDetail->getWorkDetail($whereClause)->getRow();

                                if (is_null($work)) {
                                    $workHour = convertToMinutes($entryTime);
                                    $startBreakHour = $breakTime;
                                } else {
                                    $workHour = convertToMinutes($work->startwork);
                                    $startBreakHour = $work->breakstart;
                                }

                                $date = date('Y-m-d', strtotime($val['checktime']));
                                $checkTime = date('H:i:s', strtotime($val['checktime']));

                                $attDetail = $mAttend->getAttendance([
                                    'nik'           => $val['nik'],
                                    'date'          => date("Y-m-d", strtotime($val['checktime'])),
                                ])->getRow();

                                if ($attDetail) {
                                    $clockIn = $attDetail->clock_in;
                                    $clockOut = $attDetail->clock_out;
                                    $workTime = convertToMinutes($clockIn);
                                    $workEndTime = convertToMinutes($clockOut);

                                    $allowIn = $mAllowance->where([
                                        'md_employee_id'                            => $list->md_employee_id,
                                        'date_format(submissiondate, "%Y-%m-%d")'   => $date,
                                        'date_format(submissiondate, "%H-%i-%s") <' => $val['checktime'],
                                        'table'                                     => $mAttend->table
                                    ])->first();

                                    $allowOut = $mAllowance->where([
                                        'md_employee_id'                            => $list->md_employee_id,
                                        'date_format(submissiondate, "%Y-%m-%d")'   => $date,
                                        'date_format(submissiondate, "%H-%i-%s") >' => $startBreakHour,
                                        'table'                                     => $mAttend->table
                                    ])->first();

                                    $ruleDetail = $mRuleDetail->where([
                                        "isactive"  => "Y",
                                        "name <>"   => null
                                    ])->findAll();

                                    if ($clockIn && $clockIn < $startBreakHour && is_null($allowIn)) {
                                        foreach ($ruleDetail as $detail) {
                                            if (
                                                $detail->name === "Terlambat 1/2 Hari"
                                                && getOperationResult($workTime, ($workHour + $detail->condition), $detail->operation)
                                            ) {
                                                $amount = $detail->value;
                                            } else if (
                                                $detail->name === "Pulang Cepat 1/2 Hari"
                                                && getOperationResult($workTime, ($workHour + $detail->condition), $detail->operation)
                                            ) {
                                                $amount = $detail->value;
                                            }
                                        }
                                    }

                                    if ($clockOut && $clockOut > $startBreakHour && is_null($allowOut)) {
                                        foreach ($ruleDetail as $detail) {
                                            if (
                                                $detail->name === "Pulang Cepat 1/2 Hari"
                                                && getOperationResult($workEndTime, ($workHour + $detail->condition), $detail->operation)
                                                // && $clockIn
                                            ) {
                                                $amount = $detail->value;
                                            } else if (
                                                $detail->name === "Terlambat"
                                                && getOperationResult($workEndTime, ($workHour + $detail->condition), $detail->operation)
                                                // && is_null($clockIn)
                                            ) {
                                                $amount = $detail->value;
                                            }
                                        }
                                    }

                                    if ($amount != 0) {
                                        $entity = new \App\Entities\AllowanceAtt();

                                        $entity->record_id = $att->trx_attend_id;
                                        $entity->table = $mAttend->table;
                                        $entity->submissiontype = 0;
                                        $entity->submissiondate = $val['checktime'];
                                        $entity->md_employee_id = $list->md_employee_id;
                                        $entity->amount = $amount;
                                        // $entity->created_by = $updated_by;
                                        // $entity->updated_by = $updated_by;

                                        $mAllowance->save($entity);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $textResponse = "OK :" . $jml;

            return $this->respond($textResponse, 200)
                ->setHeader('Content-Type', 'text/plain');
        } catch (\Exception $e) {
            return $this->respond($e->getMessage(), 400);
        }
    }
}

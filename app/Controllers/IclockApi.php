<?php

namespace App\Controllers;

use App\Models\M_Absent;
use App\Models\M_AbsentDetail;
use CodeIgniter\RESTful\ResourceController;
use App\Models\M_AllowanceAtt;
use App\Models\M_Attendance;
use App\Models\M_AttendanceMachine;
use App\Models\M_Configuration;
use App\Models\M_Employee;
use App\Models\M_EmpWorkDay;
use App\Models\M_Rule;
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
        $this->model = new M_Attendance($this->request);
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
TransTimes=00:00;14:05
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
        $get = $this->request->getGet();

        try {
            $content = trim($this->request->getBody());
            $arr = preg_split('/\\r\\n|\\r|,|\\n/', $content, -1, PREG_SPLIT_NO_EMPTY);
            $jml = 0;

            if (isset($get['table']) && $get['table'] === "ATTLOG") {
                $data = [];

                foreach ($arr as $val) {
                    $row = [];
                    $req = preg_split('/\\t\\n|\\t|,|\\n/', $val);

                    $row['nik'] = $req[0];
                    $row['checktime'] = $req[1];
                    $row['status'] = $req[2];
                    $row['verify'] = $req[3];
                    $row['reserved'] = $req[4];
                    $row['reserved2'] = $req[5];
                    $row['serialnumber'] = $get['SN'];
                    $data[] = $row;

                    $jml++;
                }

                if ($this->model->builder->insertBatch($data) > 0) {
                    $this->logAttendance($data);
                }
            }

            $textResponse = "OK :" . $jml;

            return $this->respond($textResponse, 200)
                ->setHeader('Content-Type', 'text/plain');
        } catch (\Exception $e) {
            return $this->respond($e->getMessage(), 400);
        }
    }

    public function getRequest()
    {
        // $commandString = "C:1:DATA QUERY ATTLOG StartTime=2024-07-08 00:00:00  EndTime=2024-07-11 23:59:59";
        // $commandString = "C:2:REBOOT";
        $get = $this->request->getGet();
        $commandString = "OK";
        $sn = $get['SN'];

        //  log_message('notice', "SN=$sn");

        //     if ($get['SN'] === 'NJF7245000774') {
        // //         // $commandString = "C:1:DATA QUERY ATTLOG StartTime=2025-08-27 00:00:00 EndTime=2025-08-27 23:59:59";
        //         $commandString = "C:2:REBOOT";
        // }
        // $commandString = "C:2:REBOOT";

        $textResponse = <<<STR
{$commandString}
STR;
        return $this->respond($textResponse, 200)
            ->setHeader('Content-Type', 'text/plain');
    }

    //     public function command()
    //     {
    //         $get = $this->request->getGet();
    //         $content = $this->request->getBody();
    //         $arr = preg_split('/\\r\\n|\\r|,|\\n/', trim($content), -1, PREG_SPLIT_NO_EMPTY);
    //         $jml = 0;
    //         $data = [];

    //         // foreach ($arr as $key => $rey) {
    //         $row = [];
    //         $row['pin'] = implode(" ", $arr);
    //         $data[] = $row;

    //         $jml++;
    //         // }


    //         $db = db_connect();
    //         $builder = $db->table('trx_att');
    //         $builder->insertBatch($data);

    //         $textResponse = "OK";

    //         return $this->respond($textResponse, 200)
    //             ->setHeader('Content-Type', 'text/plain');
    //     }

    public function updateLastLog()
    {
        $mMachine = new M_AttendanceMachine($this->request);
        $json = $this->request->getJSON(true);
        $sn = $json['SN'] ?? null;
        $lastSent = $json['last_sent'] ?? null;

        if (!$sn || !$lastSent) {
            return $this->response->setStatusCode(400)->setJSON([
                'error' => 'SN and last_sent required'
            ]);
        }

        $machine = $mMachine->where("serialnumber", $sn)->first();

        if (!$machine) {
            return $this->response->setStatusCode(404)->setJSON([
                'error' => 'Machine not found'
            ]);
        }

        try {
            // Update record
            $mMachine->update($machine->md_attendance_machines_id, [
                'last_sent' => $lastSent
            ]);

            return $this->response->setStatusCode(200)->setJSON([
                'status'    => 'Ok',
                'SN'        => $sn,
                'last_sent' => $lastSent
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'error' => $e->getMessage()
            ]);
        }
    }

    public function lastLogMachine()
    {
        $mMachine = new M_AttendanceMachine($this->request);
        $sn = $this->request->getGet('SN');

        $machine = $mMachine->where("serialnumber", $sn)->first();

        if ($machine) {
            try {
                $lastSent = $machine->last_sent
                    ? (new \DateTime($machine->last_sent))->format('Y-m-d H:i:s')
                    : '1970-01-01 00:00:00';

                return $this->response->setStatusCode(200)->setJSON([
                    'last_sent' => $lastSent
                ]);
            } catch (\Exception $e) {
                return $this->response->setStatusCode(400)->setJSON([
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $this->response->setStatusCode(404)->setJSON([
            'message' => 'Machine not found'
        ]);
    }

    public function receiveLocal()
    {
        $json = $this->request->getJSON(true);
        $sn = $this->request->getGet('SN') ?? null;
        $table = $this->request->getGet('table');

        try {
            $jml  = 0;

            if (isset($table) && $table === "ATTLOG") {
                $data = [];

                if (isset($json['uid'])) {
                    $json = [$json];
                }

                foreach ($json as $val) {
                    $row = [
                        'nik'         => $val['uid'] ?? null,
                        'checktime'   => $val['timestamp'] ?? null,
                        'status'      => $val['status'] ?? null,
                        'verify'      => $val['verif_mode'] ?? null,
                        'reserved'    => $val['reserved_1'] ?? null,
                        'reserved2'   => $val['reserved_2'] ?? null,
                        'serialnumber' => $sn,
                    ];

                    $data[] = $row;
                    $jml++;
                }

                if ($this->model->builder->insertBatch($data) > 0) {
                    $this->logAttendance($data);
                }
            }

            $response = "OK :" . $jml;
            return $this->response->setStatusCode(200)->setJSON($response);
        } catch (\Exception $e) {
            return $this->respond($e->getMessage(), 400);
        }
    }

    protected function logAttendance($data)
    {
        $mEmployee = new M_Employee($this->request);
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mAllowance = new M_AllowanceAtt($this->request);
        $mRule = new M_Rule($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);
        $mAbsent = new M_Absent($this->request);
        $mAbsentDetail = new M_AbsentDetail($this->request);
        $mConfig = new M_Configuration($this->request);

        $rule = $mRule->whereIn('name', ['Pulang Cepat', 'Datang Terlambat'])->findAll();

        $ruleID = array_column($rule, 'md_rule_id');

        $ruleDetail = $mRuleDetail->where([
            "isactive"  => "Y",
            "name <>"   => null
        ])->whereIn('md_rule_id', $ruleID)->findAll();

        // TODO : Getting Configuration Manager not need Special Office Duties
        $configMNSOD = $mConfig->where('name', 'MANAGER_NO_NEED_SPECIAL_OFFICE_DUTIES')->first();

        $configMNSOD = $configMNSOD->value == 'Y' ? true : false;
        $lvlManager = 100003;

        foreach ($data as $val) {
            $emp = $mEmployee->where("nik", $val['nik'])->first();

            if ($emp) {
                $date = date('Y-m-d', strtotime($val['checktime']));

                $attToday = $this->model->getAttendance([
                    'v_attendance.nik'        => $val['nik'],
                    'v_attendance.date'       => $date
                ])->getRow();

                //TODO: Masukan data allowance hari sebelumnya jika tidak ada absen pulang 
                if ($attToday) {
                    $day = strtoupper(formatDay_idn(date('w', strtotime($attToday->date))));

                    $whereClause = "md_work_detail.isactive = 'Y'";
                    $whereClause .= " AND md_employee_work.md_employee_id = $emp->md_employee_id";
                    $whereClause .= " AND (md_employee_work.validfrom <= '{$date}' and md_employee_work.validto >= '{$date}')";
                    $whereClause .= " AND md_day.name = '$day'";
                    $work = $mEmpWork->getEmpWorkDetail($whereClause)->getRow();

                    $whereClause = "trx_absent.md_employee_id = $emp->md_employee_id";
                    $whereClause .= " AND DATE_FORMAT(trx_absent_detail.date, '%Y-%m-%d') = '{$date}'";
                    $whereClause .= " AND trx_absent.submissiontype IN ({$mAbsent->Pengajuan_Ijin}, {$mAbsent->Pengajuan_Sakit}, {$mAbsent->Pengajuan_Cuti}, {$mAbsent->Pengajuan_Tugas_Kantor}, {$mAbsent->Pengajuan_Ijin_Resmi})";
                    $whereClause .= " AND trx_absent.docstatus IN ('CO', 'IP')";
                    $whereClause .= " AND trx_absent_detail.isagree IN ('Y', 'M', 'S')";
                    $trxPresentDay = $mAbsentDetail->getAbsentDetail($whereClause)->getRow();

                    if (($work || ($configMNSOD && $emp->md_levelling_id <= $lvlManager)) && !$trxPresentDay) {
                        //TODO : Insert beginning balance
                        $mAllowance->insertAllowance(null, 'trx_attendance', !empty($work) ? 'S+' : 'A+', $val['checktime'], null, $emp->md_employee_id, 1);

                        $clockIn = $attToday->clock_in;
                        $clockOut = $attToday->clock_out;
                        $workInTime = convertToMinutes($clockIn);
                        $workOutTime = convertToMinutes($clockOut);
                        $workHour = !empty($work) ? convertToMinutes($work->startwork) : convertToMinutes("08:30");
                        $workHourEnd = !empty($work) ? convertToMinutes($work->endwork) : convertToMinutes("15:30");
                        $breakStart = !empty($work) ? convertToMinutes($work->breakstart) : convertToMinutes("12:00");

                        $amount = 0;
                        $formType = 0;

                        foreach ($ruleDetail as $detail) {
                            if (
                                $detail->name === "Terlambat 1/2 Hari"
                                && getOperationResult($workInTime, ($workHour + $detail->condition), $detail->operation)
                                && !empty($clockIn)
                            ) {
                                $allowIn = $mAllowance->where([
                                    'md_employee_id'    => $emp->md_employee_id,
                                    'DATE(submissiondate) =' => $date,
                                    'submissiontype'    => $mAbsent->Pengajuan_Datang_Terlambat,
                                    'table'             => $this->model->table
                                ])->first();
                                logMessage($allowIn);
                                if (is_null($allowIn)) {
                                    $amount = $detail->value;
                                    $formType = $mAbsent->Pengajuan_Datang_Terlambat;
                                }
                            }

                            if (
                                $detail->name === "Terlambat"
                                && getOperationResult($workOutTime, $breakStart, $detail->operation)
                                && getOperationResult($workOutTime, $workHourEnd, '<<')
                                && empty($clockIn) && !empty($clockOut)
                            ) {
                                $allowOut = $mAllowance->where([
                                    'md_employee_id'    => $emp->md_employee_id,
                                    'DATE(submissiondate) ='    => $date,
                                    'submissiontype'    => $mAbsent->Pengajuan_Datang_Terlambat,
                                    'table'             => $this->model->table
                                ])->first();

                                if (is_null($allowOut)) {
                                    $amount = $detail->value;
                                    $formType = $mAbsent->Pengajuan_Datang_Terlambat;
                                }
                            }

                            // TODO : Only Inserting when Manager had absent on out of range Manager Workday
                            if (
                                $detail->name === "Pulang Cepat 1/2 Hari"
                                && getOperationResult($workOutTime, $workHourEnd, $detail->operation)
                                && !empty($clockIn) && !empty($clockOut) && empty($work)
                                && ($configMNSOD && $emp->md_levelling_id <= $lvlManager)
                            ) {
                                $allowOut = $mAllowance->where([
                                    'md_employee_id'    => $emp->md_employee_id,
                                    'DATE(submissiondate) ='  => $date,
                                    'submissiontype'    => $mAbsent->Pengajuan_Pulang_Cepat,
                                    'table'             => $this->model->table
                                ])->first();

                                if (is_null($allowOut)) {
                                    $amount = $detail->value;
                                    $formType = $mAbsent->Pengajuan_Pulang_Cepat;
                                }
                            }
                        }

                        //TODO : Insert Allowance
                        if ($amount != 0) {
                            $mAllowance->insertAllowance(null, $this->model->table, 'A-', $val['checktime'], $formType, $emp->md_employee_id, $amount);
                        }
                    }
                }
            }
        }
    }
}

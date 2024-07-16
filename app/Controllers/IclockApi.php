<?php

namespace App\Controllers;

use App\Models\M_Absent;
use CodeIgniter\RESTful\ResourceController;
use App\Models\M_Attend;
use App\Models\M_AllowanceAtt;
use App\Models\M_Employee;
use App\Models\M_EmpWorkDay;
use App\Models\M_Rule;
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
        $mRule = new M_Rule($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);
        $mAbsent = new M_Absent($this->request);

        $get = $this->request->getGet();

        $today = date('Y-m-d');
        // $prevDate = date('Y-m-d', strtotime('-1 days'));
        $day = date('w');
        $workHour = convertToMinutes("08:00");
        $breakTime = "12:00";
        $amount = 0;
        $formType = 0;

        try {
            $content = $this->request->getBody();
            $arr = preg_split('/\\r\\n|\\r|,|\\n/', trim($content), -1, PREG_SPLIT_NO_EMPTY);
            $jml = 0;

            if (isset($get['table']) && $get['table'] === "ATTLOG") {
                $data = [];

                foreach ($arr as $key => $val) {
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

                $result = $mAttend->builder->insertBatch($data);

                if ($result > 0) {
                    foreach ($data as $val) {
                        $list = $mEmployee->where("nik", $val['nik'])->first();

                        if ($list) {
                            //TODO : Get work day employee
                            $workDay = $mEmpWork->where([
                                'md_employee_id'    => $list->md_employee_id,
                                'validfrom <='      => $today
                            ])->orderBy('validfrom', 'ASC')->first();

                            $attToday = $mAttend->getAttendance([
                                'nik'        => $val['nik'],
                                'date'       => date("Y-m-d", strtotime($val['checktime']))
                            ])->getRow();

                            //TODO: Masukan data allowance hari sebelumnya jika tidak ada absen pulang 
                            // if ($prevAtt && !empty($prevAtt->clock_in) && empty($prevAtt->clock_out)) {
                            //     $day = date('w', strtotime($prevAtt->date));
                            //     $day = strtoupper(formatDay_idn($day));

                            //     $whereClause = "md_work_detail.isactive = 'Y'";
                            //     $whereClause .= " AND md_employee_work.md_employee_id = $list->md_employee_id";
                            //     $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
                            //     $whereClause .= " AND md_day.name = '$day'";
                            //     $work = $mWorkDetail->getWorkDetail($whereClause)->getRow();

                            //     if ($work) {
                            //         $rule = $mRule->where([
                            //             "isactive"  => "Y",
                            //             "name"      => "Lupa Absen Pulang"
                            //         ])->first();

                            //         $amount = $rule && $rule->condition ?: $rule->value;

                            //         $allowOut = $mAllowance->where([
                            //             'md_employee_id'                            => $list->md_employee_id,
                            //             'date_format(submissiondate, "%Y-%m-%d")'   => $prevAtt->date,
                            //             'submissiontype'                            => $mAbsent->Pengajuan_Lupa_Absen_Pulang,
                            //             'table'                                     => $mAttend->table
                            //         ])->first();

                            //         if ($amount != 0 && is_null($allowOut)) {
                            //             $entity = new \App\Entities\AllowanceAtt();

                            //             // $entity->record_id = $prevAtt->trx_attend_id;
                            //             $entity->table = $mAttend->table;
                            //             $entity->submissiontype = $mAbsent->Pengajuan_Lupa_Absen_Pulang;
                            //             $entity->submissiondate = $prevAtt->date;
                            //             $entity->md_employee_id = $list->md_employee_id;
                            //             $entity->amount = $amount;
                            //             // $entity->created_by = $updated_by;
                            //             // $entity->updated_by = $updated_by;

                            //             $mAllowance->save($entity);
                            //         }
                            //     }
                            // }

                            //TODO: Masukan data allowance hari sebelumnya jika tidak ada absen pulang 
                            if ($attToday) {
                                $day = date('w', strtotime($attToday->date));
                                $day = strtoupper(formatDay_idn($day));

                                $whereClause = "md_work_detail.isactive = 'Y'";
                                $whereClause .= " AND md_employee_work.md_employee_id = $list->md_employee_id";
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
                                            $dateTime = date("Y-m-d", strtotime($val['checktime'])) . " " . $checkTime;

                                            $allowIn = $mAllowance->where([
                                                'md_employee_id'    => $list->md_employee_id,
                                                'submissiondate'    => $dateTime,
                                                'submissiontype'    => $mAbsent->Pengajuan_Datang_Terlambat,
                                                'table'             => $mAttend->table
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
                                            $dateTime = date("Y-m-d", strtotime($val['checktime'])) . " " . $checkTime;

                                            $allowOut = $mAllowance->where([
                                                'md_employee_id'    => $list->md_employee_id,
                                                'submissiondate <'  => $dateTime,
                                                'submissiontype'    => $mAbsent->Pengajuan_Datang_Terlambat,
                                                'table'             => $mAttend->table
                                            ])->first();

                                            if (is_null($allowOut)) {
                                                $amount = $detail->value;
                                                $formType = $mAbsent->Pengajuan_Datang_Terlambat;
                                            }
                                        }

                                        if (
                                            $detail->name === "Pulang Cepat 1/2 Hari"
                                            && getOperationResult($workOutTime, ($endBreakHour + $detail->condition), $detail->operation)
                                            && !empty($clockIn) && !empty($clockOut)
                                        ) {
                                            $checkTime = $clockOut;
                                            $dateTime = date("Y-m-d", strtotime($val['checktime'])) . " " . $checkTime;

                                            $allowOut = $mAllowance->where([
                                                'md_employee_id'    => $list->md_employee_id,
                                                'submissiondate <'  => $dateTime,
                                                'submissiontype'    => $mAbsent->Pengajuan_Pulang_Cepat,
                                                'table'             => $mAttend->table
                                            ])->first();

                                            if (is_null($allowOut)) {
                                                $amount = $detail->value;
                                                $formType = $mAbsent->Pengajuan_Pulang_Cepat;
                                            }
                                        }
                                    }

                                    $allowSub = $mAllowance->where([
                                        'md_employee_id'    => $list->md_employee_id,
                                        'submissiondate'    => date("Y-m-d", strtotime($val['checktime'])),
                                        'table'             => $mAttend->table
                                    ])->whereIn(
                                        'submissiontype',
                                        [$mAbsent->Pengajuan_Tugas_Khusus, $mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari]
                                    )->first();

                                    if ($amount != 0 && is_null($allowSub)) {
                                        $entity = new \App\Entities\AllowanceAtt();

                                        $att = $mAttend->where([
                                            'nik'           => $val['nik'],
                                            'checktime'     => date("Y-m-d", strtotime($val['checktime'])) . " " . $checkTime
                                        ])->first();

                                        $entity->record_id = $att->trx_attend_id;
                                        $entity->table = $mAttend->table;
                                        $entity->submissiontype = $formType;
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

            // if (isset($get['table']) && $get['table'] === "OPERLOG") {
            //     $data = [];

            //     foreach ($arr as $key => $rey) {
            //         // $row = [];
            //         // $req = preg_split('/\\t\\n|\\t|,|\\n/', $rey);

            //         // $row['nik'] = $req[0];
            //         // $row['checktime'] = $req[1];
            //         // $row['status'] = $req[2];
            //         // $row['verify'] = $req[3];
            //         // $row['reserved'] = $req[4];
            //         // $row['reserved2'] = $req[5];
            //         // $row['serialnumber'] = $get['SN'];
            //         // $data[] = $row;

            //         $row = [];
            //         $row['pin'] = implode(" ", $arr);
            //         $data[] = $row;

            //         $jml++;
            //     }


            //     $db = db_connect();
            //     $builder = $db->table('trx_att');
            //     $builder->insertBatch($data);
            // }

            $textResponse = "OK :" . $jml;

            return $this->respond($textResponse, 200)
                ->setHeader('Content-Type', 'text/plain');
        } catch (\Exception $e) {
            return $this->respond($e->getMessage(), 400);
        }
    }

    //     public function getRequest()
    //     {
    //         // $commandString = "C:1:DATA QUERY ATTLOG StartTime=2024-07-08 00:00:00  EndTime=2024-07-11 23:59:59";
    //         // $commandString = "C:2:REBOOT";
    //         $get = $this->request->getGet();
    //         $commandString = "OK";

    //         if ($get['SN'] === 'NJF7235201141')
    //             $commandString = "C:2:REBOOT";

    //         $textResponse = <<<STR
    // {$commandString}
    // STR;
    //         return $this->respond($textResponse, 200)
    //             ->setHeader('Content-Type', 'text/plain');
    //     }

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
}

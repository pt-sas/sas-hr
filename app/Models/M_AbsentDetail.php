<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_AbsentDetail extends Model
{
    protected $table                = 'trx_absent_detail';
    protected $primaryKey           = 'trx_absent_detail_id';
    protected $allowedFields        = [
        'trx_absent_id',
        'lineno',
        'date',
        'isagree',
        'ref_absent_detail_id',
        'table',
        'created_by',
        'updated_by',
        'image',
        'approve_date',
        'is_generated_memo',
        'realization_date_superior',
        'realization_by_superior',
        'realization_date_hrd',
        'realization_by_hrd',
        'isreopen'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\AbsentDetail';
    protected $allowCallbacks       = true;
    protected $beforeInsert         = [];
    protected $afterInsert          = ['createAllowance'];
    protected $beforeUpdate         = [];
    protected $afterUpdate          = ['createAllowance'];
    protected $beforeDelete         = [];
    protected $afterDelete          = [];
    protected $request;
    protected $db;
    protected $builder;

    public function __construct(RequestInterface $request)
    {
        parent::__construct();
        $this->db = db_connect();
        $this->request = $request;
        $this->builder = $this->db->table($this->table);
    }

    public function getDetail($field, $where)
    {
        $this->builder->select($this->table . '.*,
            trx_absent.trx_absent_id,
            trx_absent.documentno');

        $this->builder->join('trx_absent', 'trx_absent.trx_absent_id = ' . $this->table . '.trx_absent_id', 'left');

        if (!empty($where)) {
            $this->builder->where($field, $where);
        }

        return $this->builder->get();
    }

    public function getAbsentDetail($where)
    {
        $this->builder->select($this->table . '.*,
            trx_absent.trx_absent_id,
            trx_absent.md_employee_id,
            trx_absent.nik,
            trx_absent.documentno,
            trx_absent.startdate,
            trx_absent.enddate,
            trx_absent.submissiontype,
            trx_absent.startdate_realization,
            trx_absent.enddate_realization');

        $this->builder->join('trx_absent', 'trx_absent.trx_absent_id = ' . $this->table . '.trx_absent_id', 'left');
        $this->builder->where($where);
        return $this->builder->get();
    }

    public function getLineNo($where)
    {
        $this->builder->select($this->table . '.*,
            trx_absent.trx_absent_id');
        $this->builder->join('trx_absent', 'trx_absent.trx_absent_id = ' . $this->table . '.trx_absent_id', 'left');
        $this->builder->where($where);

        $sql = $this->builder->get();

        $lineNo = $sql->getNumRows() + 1;

        return $lineNo;
    }

    public function createAllowance(array $rows)
    {
        $mAbsent = new M_Absent($this->request);
        $mRule = new M_Rule($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);
        $mAllowance = new M_AllowanceAtt($this->request);
        $mLeaveBalance = new M_LeaveBalance($this->request);
        $mTransaction = new M_Transaction($this->request);
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);

        $amount = 0;

        $ID = isset($rows['id'][0]) ? $rows['id'][0] : $rows['id'];
        $updated_by = !empty($rows['data']['updated_by']) ?? session()->get('sys_user_id');

        $line = $this->find($ID);
        $sql = $mAbsent->where($mAbsent->primaryKey, $line->{$mAbsent->primaryKey})->first();

        try {
            $ruleDetail = null;
            $year = date('Y', strtotime($line->date));
            $date = date('Y-m-d', strtotime($line->date));

            if ($line->isagree === "Y") {
                if ($sql->submissiontype == $mAbsent->Pengajuan_Cuti) {
                    $rule = $mRule->where([
                        'name'      => 'Cuti',
                        'isactive'  => 'Y'
                    ])->first();

                    $balance = $mLeaveBalance->where([
                        'year'              => $year,
                        'md_employee_id'    => $sql->md_employee_id
                    ])->first();

                    $carryOverValid = ($balance->carry_over_expiry_date && $date <= date('Y-m-d', strtotime($balance->carry_over_expiry_date)));
                    $mainLeaveValid = ($balance->enddate && $date <= date('Y-m-d', strtotime($balance->enddate)));

                    $dataLeaveUsage = [];
                    $updateField = null;
                    $oldValue = null;
                    $newValue = null;

                    $entityBal = new \App\Entities\LeaveBalance();
                    $entityBal->md_employee_id = $sql->md_employee_id;
                    $entityBal->trx_leavebalance_id = $balance->trx_leavebalance_id;

                    $rsvdTransaction = $mTransaction->where(['record_id' => $ID, 'table' => 'trx_absent_detail', 'transactiontype' => 'R+'])->first();

                    if ($carryOverValid && $balance->carried_over_amount > 0) {
                        $updateField = 'carried_over_amount';
                        $oldValue = $balance->carried_over_amount;
                        $newValue = $oldValue - 1;
                        $entityBal->$updateField = $newValue;
                    } else if ($mainLeaveValid && $balance->balance_amount > 0) {
                        $updateField = 'balance_amount';
                        $oldValue = $balance->balance_amount;
                        $newValue = $oldValue - 1;
                        $entityBal->$updateField = $newValue;
                    } else {
                        $entity = new \App\Entities\AbsentDetail();
                        $entity->isagree = "N";
                        $entity->updated_by = $updated_by;
                        $entity->{$this->primaryKey} = $ID;
                        $this->save($entity);
                        return;
                    }

                    if ($mLeaveBalance->save($entityBal)) {
                        if ($rsvdTransaction) {
                            $dataLeaveUsage[] = [
                                'record_id'       => $ID,
                                'table'           => $this->table,
                                'transactiondate' => $line->date,
                                'transactiontype' => 'C-',
                                'year'            => $year,
                                'amount'          => -$rsvdTransaction->reserved_amount,
                                'reserved_amount' => -$rsvdTransaction->reserved_amount,
                                'md_employee_id'  => $sql->md_employee_id,
                                'isprocessed'     => 'N',
                                'created_by'      => $updated_by,
                                'updated_by'      => $updated_by
                            ];
                        } else {
                            $dataLeaveUsage[] = [
                                'record_id'       => $ID,
                                'table'           => $this->table,
                                'transactiondate' => $line->date,
                                'transactiontype' => 'C-',
                                'year'            => $year,
                                'amount'          => -1,
                                'md_employee_id'  => $sql->md_employee_id,
                                'isprocessed'     => 'N',
                                'created_by'      => $updated_by,
                                'updated_by'      => $updated_by
                            ];
                        }

                        $mTransaction->builder->insertBatch($dataLeaveUsage);

                        $amount = $rule->condition ?: $rule->value;
                    }
                }

                if ($sql->submissiontype == $mAbsent->Pengajuan_Sakit) {
                    $rule = $mRule->where([
                        'name'      => 'Sakit',
                        'isactive'  => 'Y'
                    ])->first();

                    if ($rule) {
                        $amount = $rule->condition ?: $rule->value;
                    }
                }

                if ($sql->submissiontype == $mAbsent->Pengajuan_Alpa) {
                    $rule = $mRule->where([
                        'name'      => 'Alpa',
                        'isactive'  => 'Y'
                    ])->first();

                    if ($rule) {
                        $amount = $rule->condition ?: $rule->value;

                        $ruleDetail = $mRuleDetail->where($mRule->primaryKey, $rule->md_rule_id)->findAll();

                        if ($ruleDetail) {
                            $balance = $mLeaveBalance->getTotalBalance($sql->md_employee_id, $year);

                            $saldo = 0;

                            if ($balance && $balance->balance > 0) {
                                $saldo = $balance->balance;
                            }

                            $dataLeaveUsage = [];
                            foreach ($ruleDetail as $detail) {
                                if ($saldo != 0) {
                                    if ($detail->name === "Sanksi Alpa Cuti") {
                                        $entityBal = new \App\Entities\LeaveBalance();
                                        $tkh = $detail->value;

                                        $calculate = $saldo + $tkh;
                                        if ($tkh != 0) {
                                            if ($calculate > 0) {
                                                $entityBal->md_employee_id = $sql->md_employee_id;
                                                $entityBal->balance_amount = $balance->balance_amount + $tkh;
                                                $entityBal->trx_leavebalance_id = $balance->trx_leavebalance_id;

                                                if ($mLeaveBalance->save($entityBal)) {
                                                    $dataLeaveUsage[] = [
                                                        "record_id"         => $ID,
                                                        "table"             => $this->table,
                                                        "transactiondate"   => $line->date,
                                                        "transactiontype"   => 'A-',
                                                        "year"              => $year,
                                                        "amount"            => $tkh,
                                                        "md_employee_id"    => $sql->md_employee_id,
                                                        "isprocessed"       => "N",
                                                        "created_by"        => $updated_by,
                                                        "updated_by"        => $updated_by
                                                    ];
                                                }

                                                $amount = 0;
                                            } else if ($saldo != 0) {
                                                $entityBal->md_employee_id = $sql->md_employee_id;
                                                $entityBal->balance_amount = $balance->balance_amount - $saldo;
                                                $entityBal->trx_leavebalance_id = $balance->trx_leavebalance_id;

                                                if ($mLeaveBalance->save($entityBal)) {
                                                    $dataLeaveUsage[] = [
                                                        "record_id"         => $ID,
                                                        "table"             => $this->table,
                                                        "transactiondate"   => $line->date,
                                                        "transactiontype"   => 'A-',
                                                        "year"              => $year,
                                                        "amount"            => - ($saldo),
                                                        "md_employee_id"    => $sql->md_employee_id,
                                                        "isprocessed"       => "N",
                                                        "created_by"        => $updated_by,
                                                        "updated_by"        => $updated_by
                                                    ];
                                                }

                                                //? Cek perbandingan dari calculate variable 
                                                if ($calculate == 0)
                                                    $amount = 0;
                                            }

                                            if ($calculate < 0) {
                                                $entity = new \App\Entities\AllowanceAtt();

                                                $rDetail = $mRuleDetail->where([
                                                    'md_rule_id' => $rule->md_rule_id,
                                                    'name'       => 'Sanksi Alpa No Cuti'
                                                ])->first();

                                                $tkh = $rDetail->value;
                                                $hari = abs($rDetail->condition);
                                                $hari -= $saldo;
                                                $tkh *= $hari;

                                                $mAllowance->insertAllowance($sql->{$mAbsent->primaryKey}, $mAbsent->table, 'A-', $line->date, $sql->submissiontype, $sql->md_employee_id, $tkh, $updated_by);
                                            }
                                        }
                                    }
                                } else {
                                    $entity = new \App\Entities\AllowanceAtt();

                                    if ($detail->name === "Sanksi Alpa No Cuti") {
                                        $tkh = $detail->value;
                                        $tkh *= abs($detail->condition);

                                        if ($tkh != 0) {
                                            $mAllowance->insertAllowance($sql->{$mAbsent->primaryKey}, $mAbsent->table, 'A-', $line->date, $sql->submissiontype, $sql->md_employee_id, $tkh, $updated_by);
                                        }
                                    }
                                }
                            }

                            if ($dataLeaveUsage)
                                $mTransaction->builder->insertBatch($dataLeaveUsage);
                        }
                    }
                }

                if ($sql->submissiontype == $mAbsent->Pengajuan_Ijin) {
                    $rule = $mRule->where([
                        'name'      => 'Ijin',
                        'isactive'  => 'Y'
                    ])->first();

                    $balance = $mLeaveBalance->where([
                        'year'              => $year,
                        'md_employee_id'    => $sql->md_employee_id
                    ])->first();

                    $rsvdTransaction = $mTransaction->where(['record_id' => $ID, 'table' => 'trx_absent_detail', 'transactiontype' => 'R+'])->first();

                    if ($balance && ($balance->balance_amount > 0 || $balance->carried_over_amount > 0) && $rsvdTransaction) {
                        $carryOverValid = ($balance->carry_over_expiry_date && $date <= date('Y-m-d', strtotime($balance->carry_over_expiry_date)));
                        $mainLeaveValid = ($balance->enddate && $date <= date('Y-m-d', strtotime($balance->enddate)));
                        $ruleDetail = $mRuleDetail->where(['md_rule_id' => $rule->md_rule_id, 'name' => "Sanksi Ijin Cuti"])->first();
                        $conseq = $ruleDetail->value;

                        $dataLeaveUsage = [];
                        $updateField = null;
                        $oldValue = null;
                        $newValue = null;

                        $entityBal = new \App\Entities\LeaveBalance();
                        $entityBal->md_employee_id = $sql->md_employee_id;
                        $entityBal->trx_leavebalance_id = $balance->trx_leavebalance_id;

                        if ($carryOverValid && $balance->carried_over_amount > 0) {
                            $updateField = 'carried_over_amount';
                            $oldValue = $balance->carried_over_amount;
                            $newValue = $oldValue + $conseq;
                            $entityBal->$updateField = $newValue;
                        } else if ($mainLeaveValid && $balance->balance_amount > 0) {
                            $updateField = 'balance_amount';
                            $oldValue = $balance->balance_amount;
                            $newValue = $oldValue + $conseq;
                            $entityBal->$updateField = $newValue;
                        }

                        if ($mLeaveBalance->save($entityBal)) {
                            $dataLeaveUsage = [
                                'record_id'       => $ID,
                                'table'           => $this->table,
                                'transactiondate' => $line->date,
                                'transactiontype' => 'I-',
                                'year'            => $year,
                                'amount'          => -$rsvdTransaction->reserved_amount,
                                'reserved_amount' => -$rsvdTransaction->reserved_amount,
                                'md_employee_id'  => $sql->md_employee_id,
                                'isprocessed'     => 'N',
                                'created_by'      => $updated_by,
                                'updated_by'      => $updated_by
                            ];

                            $mTransaction->builder->insert($dataLeaveUsage);
                        }
                    } else {
                        $ruleDetail = $mRuleDetail->where(['md_rule_id' => $rule->md_rule_id, 'name' => "Sanksi Ijin No Cuti"])->first();
                        $amount = $ruleDetail->value;
                    }
                }

                if ($sql->submissiontype == $mAbsent->Pengajuan_Tugas_Kantor) {
                    $rule = $mRule->where([
                        'name'      => 'Tugas Kantor 1 Hari',
                        'isactive'  => 'Y'
                    ])->first();

                    if ($rule) {
                        $amount = $rule->condition ?: $rule->value;
                    }
                }

                if ($sql->submissiontype == $mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari) {
                    $rule = $mRule->where([
                        'name'      => 'Tugas Kantor Setengah Hari',
                        'isactive'  => 'Y'
                    ])->first();

                    if ($rule) {
                        $amount = $rule->condition ?: $rule->value;
                    }
                }

                if ($sql->submissiontype == $mAbsent->Pengajuan_Lupa_Absen_Masuk) {
                    $rule = $mRule->where([
                        'name'      => 'Lupa Absen Masuk',
                        'isactive'  => 'Y'
                    ])->first();

                    if ($rule) {
                        $amount = $rule->condition ?: $rule->value;
                    }
                }

                if ($sql->submissiontype == $mAbsent->Pengajuan_Lupa_Absen_Pulang) {
                    $rule = $mRule->where([
                        'name'      => 'Lupa Absen Pulang',
                        'isactive'  => 'Y'
                    ])->first();

                    if ($rule) {
                        $amount = $rule->condition ?: $rule->value;
                    }
                }

                // if ($sql->submissiontype == $mAbsent->Pengajuan_Datang_Terlambat) {
                //     $rule = $mRule->where([
                //         'name'      => 'Datang Terlambat',
                //         'isactive'  => 'Y'
                //     ])->first();

                //     if ($rule) {
                //         $amount = $rule->condition ?: $rule->value;

                //         $ruleDetail = $mRuleDetail->where($mRule->primaryKey, $rule->md_rule_id)->findAll();

                //         if ($ruleDetail) {
                //             //TODO : Get work day employee
                //             $workDay = $mEmpWork->where([
                //                 'md_employee_id'    => $sql->md_employee_id,
                //                 'validfrom <='      => $today
                //             ])->orderBy('validfrom', 'ASC')->first();

                //             if (is_null($workDay)) {
                //                 $workHour = convertToMinutes($entryTime);
                //             } else {
                //                 $day = strtoupper(formatDay_idn($day));

                //                 //TODO: Get Work Detail by day 
                //                 $work = null;

                //                 $whereClause = "md_work_detail.isactive = 'Y'";
                //                 $whereClause .= " AND md_employee_work.md_employee_id = $sql->md_employee_id";
                //                 $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
                //                 $whereClause .= " AND md_day.name = '$day'";
                //                 $work = $mWorkDetail->getWorkDetail($whereClause)->getRow();

                //                 if (is_null($work)) {
                //                     $workHour = convertToMinutes($entryTime);
                //                 } else {
                //                     $workHour = convertToMinutes($work->startwork);
                //                 }
                //             }

                //             $workTime = convertToMinutes($sql->startdate);

                //             foreach ($ruleDetail as $detail) {
                //                 if (($detail->name === "Terlambat 1/2 Hari" || $detail->name === "Terlambat") && getOperationResult($workTime, ($workHour + $detail->condition), $detail->operation)) {
                //                     $amount = $detail->value;
                //                 }
                //             }
                //         }
                //     }
                // }

                if ($sql->submissiontype == $mAbsent->Pengajuan_Pulang_Cepat) {
                    $rule = $mRule->where([
                        'name'      => 'Pulang Cepat',
                        'isactive'  => 'Y'
                    ])->first();

                    if ($rule) {
                        $amount = $rule->condition ?: $rule->value;

                        $ruleDetail = $mRuleDetail->where($mRule->primaryKey, $rule->md_rule_id)->findAll();

                        if ($ruleDetail) {
                            //TODO : Get work day employee
                            $workDay = $mEmpWork->where([
                                'md_employee_id'    => $sql->md_employee_id,
                                'validfrom <='      => $line->date,
                                'validto   >='      => $line->date
                            ])->orderBy('validfrom', 'ASC')->first();

                            if (is_null($workDay)) {
                                $workHour = convertToMinutes('08:00');
                                $breakStart =  $workHour + $ruleDetail[1]->condition;
                                $workEndHour = $workHour + $ruleDetail[0]->condition;
                            } else {
                                $day = strtoupper(formatDay_idn(date('W', strtotime($line->date))));

                                //TODO: Get Work Detail by day 
                                $work = null;

                                $whereClause = "md_work_detail.isactive = 'Y'";
                                $whereClause .= " AND md_employee_work.md_employee_id = $sql->md_employee_id";
                                $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
                                $whereClause .= " AND md_day.name = '$day'";
                                $work = $mWorkDetail->getWorkDetail($whereClause)->getRow();

                                $workHour = empty($work) ? convertToMinutes("08:00") : convertToMinutes($work->startwork);
                                $breakStart =  empty($work) ? $workHour + $ruleDetail[1]->condition : convertToMinutes($work->breakstart);
                                $workEndHour = empty($work) ? $workHour + $ruleDetail[0]->condition : convertToMinutes($work->endwork);
                            }

                            $workTime = convertToMinutes($sql->startdate);

                            foreach ($ruleDetail as $detail) {
                                if ($detail->name === "Pulang Cepat" && getOperationResult($workTime, $breakStart, $detail->operation)) {
                                    $amount = $detail->value;
                                    break;
                                } else if ($detail->name === "Pulang Cepat 1/2 Hari" && getOperationResult($workTime, $workEndHour, $detail->operation)) {
                                    $amount = $detail->value;
                                    break;
                                }
                            }
                        }
                    }
                }

                if ($amount != 0) {
                    $transactiontype = $amount < 0 ? 'A-' : 'A+';
                    $mAllowance->insertAllowance($sql->{$mAbsent->primaryKey}, $mAbsent->table, $transactiontype, $line->date, $sql->submissiontype, $sql->md_employee_id, $amount, $updated_by);
                }
            }

            if ($line->isagree === "N") {
                // TODO : Returning Reserved Leave Balance
                if ($sql->submissiontype == $mAbsent->Pengajuan_Cuti || $sql->submissiontype == $mAbsent->Pengajuan_Ijin) {
                    $rsvdTransaction = $mTransaction->where(['record_id' => $ID, 'table' => 'trx_absent_detail', 'transactiontype' => 'R+'])->first();

                    if ($rsvdTransaction) {
                        $dataLeaveUsage = [
                            'record_id'       => $ID,
                            'table'           => $this->table,
                            'transactiondate' => $line->date,
                            'transactiontype' => 'R-',
                            'year'            => $year,
                            'amount'          => 0,
                            'reserved_amount' => -$rsvdTransaction->reserved_amount,
                            'md_employee_id'  => $sql->md_employee_id,
                            'isprocessed'     => 'N',
                            'created_by'      => $updated_by,
                            'updated_by'      => $updated_by,
                            'description'     => 'Not Approved'
                        ];

                        $mTransaction->builder->insert($dataLeaveUsage);
                    }
                }
            }

            if ($line->isagree === "H" || $line->isagree === "S") {
                $rsvdTransaction = $mTransaction->where(['record_id' => $ID, 'table' => 'trx_absent_detail'])->orderBy('created_at', 'DESC')->first();

                if ($sql->submissionType == $mAbsent->Pengajuan_Cuti && (!$rsvdTransaction || $rsvdTransaction->transactiontype !== "R+")) {
                    $entity = new \App\Entities\Transaction();
                    $entity->table = $this->table;
                    $entity->transactiondate = $line->date;
                    $entity->transactiontype = 'R+';
                    $entity->year = date('Y', strtotime($line->date));
                    $entity->record_id = $line->trx_absent_detail_id;
                    $entity->amount = 0;
                    $entity->reserved_amount = 1;
                    $entity->md_employee_id = $sql->md_employee_id;
                    $entity->isprocessed = 'N';
                    $entity->created_by = $updated_by;
                    $entity->updated_by = $updated_by;

                    $mTransaction->save($entity);
                }

                if ($sql->submissionType == $mAbsent->Pengajuan_Ijin && (!$rsvdTransaction || $rsvdTransaction->transactiontype !== "R+")) {
                    $leaveBalance = $mLeaveBalance->getTotalBalance($sql->md_employee_id, $year);

                    if ($leaveBalance) {
                        // Cek apakah saldo carry over ada dan belum expired
                        $carryOverValid = ($leaveBalance->carry_over_expiry_date && $date <= date('Y-m-d', strtotime($leaveBalance->carry_over_expiry_date)));

                        // Cek apakah saldo cuti utama ada dan belum expired
                        $mainLeaveValid = ($leaveBalance->enddate && $date <= date('Y-m-d', strtotime($leaveBalance->enddate)));

                        if (($carryOverValid && $leaveBalance->balance_carried > 0) || ($mainLeaveValid && $leaveBalance->balance > 0)) {
                            $rule = $mRule->where(['name' => 'Ijin', 'isactive' => 'Y'])->first();
                            $ruleDetail = $mRuleDetail->where(['md_rule_id' => $rule->md_rule_id, 'name' => "Sanksi Ijin Cuti"])->first();

                            $entity = new \App\Entities\Transaction();
                            $entity->table = $this->table;
                            $entity->transactiondate = $line->date;
                            $entity->transactiontype = 'R+';
                            $entity->year = date('Y', strtotime($line->date));
                            $entity->record_id = $line->trx_absent_detail_id;
                            $entity->amount = 0;
                            $entity->reserved_amount = abs($ruleDetail->value);
                            $entity->md_employee_id = $sql->md_employee_id;
                            $entity->isprocessed = 'N';
                            $entity->created_by = $updated_by;
                            $entity->updated_by = $updated_by;

                            $mTransaction->save($entity);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function doAfterUpdate(array $rows)
    {
        $mAbsent = new M_Absent($this->request);
        $changeLog = new M_ChangeLog($this->request);

        try {
            $sessionUser = session()->get('sys_user_id');
            $ID = isset($rows['id'][0]) ? $rows['id'][0] : $rows['id'];
            $todayTime = date('Y-m-d H:i:s');
            $updatedBy = !empty($sessionUser) ? $sessionUser : 100000;
            $line = $this->find($ID);
            $header = $mAbsent->find($line->trx_absent_id);

            // TODO : Update Header if there no pending line
            $list = $this->where(
                'trx_absent_id',
                $line->{$mAbsent->primaryKey}
            )->whereIn('isagree', ['M', 'S', 'H'])->first();

            if (is_null($list)) {
                $dataUpdate = [
                    "docstatus"     => "CO",
                    "receiveddate"  => $todayTime,
                    "updated_by"    => $updatedBy
                ];

                $mAbsent->builder->update($dataUpdate, [$mAbsent->primaryKey => $header->trx_absent_id]);
                $changeLog->insertLog($mAbsent->table, 'docstatus', $header->trx_absent_id, $header->docstatus, "CO", 'U');
            }

            //TODO : Update Isapproved if there's no line to Approved
            $pendingLine = $this->where([
                'trx_absent_id' => $line->{$mAbsent->primaryKey},
                'isagree'       => 'H'
            ])->first();

            if (is_null($pendingLine)) {
                if (empty($header->getIsApproved())) {
                    $hadApprovedLine = $this->where(
                        'trx_absent_id',
                        $line->{$mAbsent->primaryKey}
                    )->whereIn('isagree', ['Y', 'M', 'S'])->first();

                    if (!empty($hadApprovedLine)) {
                        $dataUpdate = [
                            'updated_by'    => $updatedBy,
                            'approveddate'  => $todayTime,
                            'isapproved'    => 'Y'
                        ];
                    } else {
                        $dataUpdate = [
                            'updated_by'    => $updatedBy,
                            'approveddate'  => $todayTime,
                            'isapproved'    => 'N',
                            'docstatus'     => 'NA'
                        ];
                    }
                    $mAbsent->builder->update($dataUpdate, [$mAbsent->primaryKey => $header->trx_absent_id]);
                }
            }

            // TODO : Create Allowance
            // if ($line->isagree === "Y")
            $this->createAllowance($rows);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Change value of field data
     *
     * @param $data Data
     * @return array
     */
    public function doChangeValueField($data, $id, $dataHeader): array
    {
        $mAbsent = new M_Absent($this->request);
        $result = [];

        $number = 1;

        foreach ($data as $row) :
            if (property_exists($row, "lineno"))
                $row->lineno = $number;

            if (!property_exists($row, "isagree")) {
                $header = $mAbsent->find($row->trx_absent_id);

                $formAttendance = [$mAbsent->Pengajuan_Lupa_Absen_Masuk, $mAbsent->Pengajuan_Lupa_Absen_Pulang, $mAbsent->Pengajuan_Datang_Terlambat, $mAbsent->Pengajuan_Pulang_Cepat];
                if (in_array($header->submissiontype, $formAttendance)) {
                    $row->isagree = "M";
                } else {
                    $row->isagree = "H";
                }
            }

            $result[] = $row;
            $number++;
        endforeach;

        return $result;
    }

    public function getAllSubmission($where)
    {
        $builder = $this->db->table("v_realization");

        // $this->builder->join('trx_absent', 'trx_absent.trx_absent_id = ' . $this->table . '.trx_absent_id', 'left');

        if ($where)
            $builder->where($where);

        return $builder->get();
    }


    public function getRealization($where)
    {
        $builder = $this->db->table("v_realization_new");

        if ($where)
            $builder->where($where);

        return $builder->get();
    }
}

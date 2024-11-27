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
            trx_absent.nik,
            trx_absent.documentno,
            trx_absent.startdate,
            trx_absent.enddate,
            trx_absent.submissiontype,
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
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);
        $mHoliday = new M_Holiday($this->request);
        $mTransaction = new M_Transaction($this->request);

        $amount = 0;

        $ID = isset($rows['id'][0]) ? $rows['id'][0] : $rows['id'];
        $updated_by = $rows['data']['updated_by'] ?? session()->get('id');;
        $today = date('Y-m-d');
        $day = date('w');
        $entryTime = "08:00";

        $line = $this->find($ID);
        $sql = $mAbsent->where($mAbsent->primaryKey, $line->{$mAbsent->primaryKey})->first();

        try {
            $ruleDetail = null;
            $year = date('Y', strtotime($line->date));

            if ($sql->submissiontype == $mAbsent->Pengajuan_Cuti && $line->isagree === "Y") {
                $balance = $mLeaveBalance->where([
                    'year'              => date("Y", strtotime($sql->startdate)),
                    'md_employee_id'    => $sql->md_employee_id
                ])->first();

                $saldo = $balance->balance_amount;
                $carriedOverAmt = $balance->carried_over_amount;
                $carryOverValid = ($balance->carry_over_expiry_date && $line->date <= date('Y-m-d', strtotime($balance->carry_over_expiry_date)));
                $mainLeaveValid = ($balance->enddate && $line->date <= date('Y-m-d', strtotime($balance->enddate)));

                $dataLeaveUsage = [];
                if ($carryOverValid && $carriedOverAmt != 0) {
                    $entityBal = new \App\Entities\LeaveBalance();
                    $entityBal->md_employee_id = $sql->md_employee_id;
                    $entityBal->carried_over_amount = $carriedOverAmt - 1;
                    $entityBal->trx_leavebalance_id = $balance->trx_leavebalance_id;

                    $mLeaveBalance->save($entityBal);

                    $dataLeaveUsage[] = [
                        "transactiondate"   => $line->date,
                        "transactiontype"   => 'C-',
                        "year"              => $year,
                        "amount"            => -1,
                        "md_employee_id"    => $sql->md_employee_id,
                        "isprocessed"       => "N",
                        "created_by"        => $updated_by,
                        "updated_by"        => $updated_by
                    ];
                } else if ($mainLeaveValid && $saldo != 0) {
                    $entityBal = new \App\Entities\LeaveBalance();
                    $entityBal->md_employee_id = $sql->md_employee_id;
                    $entityBal->balance_amount = $saldo - 1;
                    $entityBal->trx_leavebalance_id = $balance->trx_leavebalance_id;

                    $mLeaveBalance->save($entityBal);

                    $dataLeaveUsage[] = [
                        "transactiondate"   => $line->date,
                        "transactiontype"   => 'C-',
                        "year"              => $year,
                        "amount"            => -1,
                        "md_employee_id"    => $sql->md_employee_id,
                        "isprocessed"       => "N",
                        "created_by"        => $updated_by,
                        "updated_by"        => $updated_by
                    ];
                } else {
                    $entity = new \App\Entities\AbsentDetail();
                    $entity->isagree = "N";
                    $entity->updated_by = $updated_by;
                    $entity->{$this->primaryKey} = $ID;
                    $this->save($entity);
                }

                if ($dataLeaveUsage)
                    $mTransaction->builder->insertBatch($dataLeaveUsage);
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
                        $balance = $mLeaveBalance->where([
                            'year'              => date("Y", strtotime($sql->startdate)),
                            'md_employee_id'    => $sql->md_employee_id
                        ])->first();
                        $saldo = $balance->balance_amount;

                        $dataLeaveUsage = [];
                        foreach ($ruleDetail as $detail) {
                            if ($saldo != 0) {
                                if ($detail->name === "Sanksi Alpa Cuti") {
                                    $entityBal = new \App\Entities\LeaveBalance();
                                    $tkh = $detail->value;

                                    $calculate = $saldo + $tkh;

                                    if ($tkh != 0 && $line->isagree === 'Y') {
                                        if ($calculate > 0) {
                                            $entityBal->md_employee_id = $sql->md_employee_id;
                                            $entityBal->balance_amount = $saldo - $tkh;
                                            $entityBal->trx_leavebalance_id = $balance->trx_leavebalance_id;

                                            $mLeaveBalance->save($entityBal);

                                            $amount = 0;

                                            $dataLeaveUsage[] = [
                                                "transactiondate"   => $line->date,
                                                "transactiontype"   => 'C-',
                                                "year"              => $year,
                                                "amount"            => $tkh,
                                                "md_employee_id"    => $sql->md_employee_id,
                                                "isprocessed"       => "N",
                                                "created_by"        => $updated_by,
                                                "updated_by"        => $updated_by
                                            ];
                                        } else if ($saldo != 0) {
                                            $entityBal->md_employee_id = $sql->md_employee_id;
                                            $entityBal->balance_amount = $saldo - $saldo;
                                            $entityBal->trx_leavebalance_id = $balance->trx_leavebalance_id;

                                            $mLeaveBalance->save($entityBal);

                                            //? Cek perbandingan dari calculate variable 
                                            if ($calculate == 0)
                                                $amount = 0;

                                            $dataLeaveUsage[] = [
                                                "transactiondate"   => $line->date,
                                                "transactiontype"   => 'C-',
                                                "year"              => $year,
                                                "amount"            => - ($saldo),
                                                "md_employee_id"    => $sql->md_employee_id,
                                                "isprocessed"       => "N",
                                                "created_by"        => $updated_by,
                                                "updated_by"        => $updated_by
                                            ];
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

                                            $entity->record_id = $sql->{$mAbsent->primaryKey};
                                            $entity->table = $mAbsent->table;
                                            $entity->submissiontype = $sql->submissiontype;
                                            $entity->submissiondate = $line->date;
                                            $entity->md_employee_id = $sql->md_employee_id;
                                            $entity->amount = $tkh;
                                            $entity->created_by = $updated_by;
                                            $entity->updated_by = $updated_by;

                                            $mAllowance->save($entity);
                                        }
                                    }
                                }
                            } else {
                                $entity = new \App\Entities\AllowanceAtt();

                                if ($detail->name === "Sanksi Alpa No Cuti") {
                                    $tkh = $detail->value;
                                    $tkh *= abs($detail->condition);

                                    if ($tkh != 0 && $line->isagree === 'Y') {
                                        $entity->record_id = $sql->{$mAbsent->primaryKey};
                                        $entity->table = $mAbsent->table;
                                        $entity->submissiontype = $sql->submissiontype;
                                        $entity->submissiondate = $line->date;
                                        $entity->md_employee_id = $sql->md_employee_id;
                                        $entity->amount = $tkh;
                                        $entity->created_by = $updated_by;
                                        $entity->updated_by = $updated_by;

                                        $mAllowance->save($entity);
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

                if ($rule) {
                    $amount = $rule->condition ?: $rule->value;

                    $ruleDetail = $mRuleDetail->where($mRule->primaryKey, $rule->md_rule_id)->findAll();

                    if ($ruleDetail) {
                        $balance = $mLeaveBalance->where([
                            'year'              => date("Y", strtotime($sql->startdate)),
                            'md_employee_id'    => $sql->md_employee_id
                        ])->first();
                        $saldo = $balance->balance_amount;

                        $dataLeaveUsage = [];
                        foreach ($ruleDetail as $detail) {
                            if ($saldo != 0) {
                                if ($detail->name === "Sanksi Ijin Cuti") {
                                    $entityBal = new \App\Entities\LeaveBalance();
                                    $tkh = $detail->value;

                                    $calculate = $saldo + $tkh;

                                    if ($tkh != 0 && $line->isagree === 'Y') {
                                        if ($calculate > 0) {
                                            $entityBal->md_employee_id = $sql->md_employee_id;
                                            $entityBal->balance_amount = $saldo - $tkh;
                                            $entityBal->trx_leavebalance_id = $balance->trx_leavebalance_id;

                                            $mLeaveBalance->save($entityBal);

                                            $amount = 0;

                                            $dataLeaveUsage[] = [
                                                "transactiondate"   => $line->date,
                                                "transactiontype"   => 'C-',
                                                "year"              => $year,
                                                "amount"            => $tkh,
                                                "md_employee_id"    => $sql->md_employee_id,
                                                "isprocessed"       => "N",
                                                "created_by"        => $updated_by,
                                                "updated_by"        => $updated_by
                                            ];
                                        } else if ($saldo != 0) {
                                            $entityBal->md_employee_id = $sql->md_employee_id;
                                            $entityBal->balance_amount = $saldo - $saldo;
                                            $entityBal->trx_leavebalance_id = $balance->trx_leavebalance_id;

                                            $mLeaveBalance->save($entityBal);

                                            //? Cek perbandingan dari calculate variable 
                                            if ($calculate == 0)
                                                $amount = 0;

                                            $dataLeaveUsage[] = [
                                                "transactiondate"   => $line->date,
                                                "transactiontype"   => 'C-',
                                                "year"              => $year,
                                                "amount"            => - ($saldo),
                                                "md_employee_id"    => $sql->md_employee_id,
                                                "isprocessed"       => "N",
                                                "created_by"        => $updated_by,
                                                "updated_by"        => $updated_by
                                            ];
                                        }

                                        if ($calculate < 0) {
                                            $entity = new \App\Entities\AllowanceAtt();

                                            $rDetail = $mRuleDetail->where([
                                                'md_rule_id' => $rule->md_rule_id,
                                                'name'       => 'Sanksi Ijin No Cuti'
                                            ])->first();

                                            $tkh = $rDetail->value;

                                            $entity->record_id = $sql->{$mAbsent->primaryKey};
                                            $entity->table = $mAbsent->table;
                                            $entity->submissiontype = $sql->submissiontype;
                                            $entity->submissiondate = $line->date;
                                            $entity->md_employee_id = $sql->md_employee_id;
                                            $entity->amount = $tkh;
                                            $entity->created_by = $updated_by;
                                            $entity->updated_by = $updated_by;

                                            $mAllowance->save($entity);
                                        }
                                    }
                                }
                            } else {
                                $entity = new \App\Entities\AllowanceAtt();

                                if ($detail->name === "Sanksi Ijin No Cuti") {
                                    $tkh = $detail->value;

                                    if ($tkh != 0 && $line->isagree === 'Y') {
                                        $entity->record_id = $sql->{$mAbsent->primaryKey};
                                        $entity->table = $mAbsent->table;
                                        $entity->submissiontype = $sql->submissiontype;
                                        $entity->submissiondate = $line->date;
                                        $entity->md_employee_id = $sql->md_employee_id;
                                        $entity->amount = $tkh;
                                        $entity->created_by = $updated_by;
                                        $entity->updated_by = $updated_by;

                                        $mAllowance->save($entity);
                                    }
                                }
                            }
                        }

                        if ($dataLeaveUsage)
                            $mTransaction->builder->insertBatch($dataLeaveUsage);
                    }
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

            // if ($sql->submissiontype == $mAbsent->Pengajuan_Lupa_Absen_Masuk) {
            //     $rule = $mRule->where([
            //         'name'      => 'Lupa Absen Masuk',
            //         'isactive'  => 'Y'
            //     ])->first();

            //     if ($rule) {
            //         $amount = $rule->condition ?: $rule->value;
            //     }
            // }

            // if ($sql->submissiontype == $mAbsent->Pengajuan_Lupa_Absen_Pulang) {
            //     $rule = $mRule->where([
            //         'name'      => 'Lupa Absen Pulang',
            //         'isactive'  => 'Y'
            //     ])->first();

            //     if ($rule) {
            //         $amount = $rule->condition ?: $rule->value;
            //     }
            // }

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

            // if ($sql->submissiontype == $mAbsent->Pengajuan_Pulang_Cepat) {
            //     $rule = $mRule->where([
            //         'name'      => 'Pulang Cepat',
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
            //                 if (($detail->name === "Pulang Cepat 1/2 Hari" || $detail->name === "Pulang Cepat") && getOperationResult($workTime, ($workHour + $detail->condition), $detail->operation)) {
            //                     $amount = $detail->value;
            //                 }
            //             }
            //         }
            //     }
            // }

            if ($amount != 0 && $line->isagree === 'Y') {
                $entity = new \App\Entities\AllowanceAtt();

                $entity->record_id = $sql->{$mAbsent->primaryKey};
                $entity->table = $mAbsent->table;
                $entity->submissiontype = $sql->submissiontype;
                $entity->submissiondate = $line->date;
                $entity->md_employee_id = $sql->md_employee_id;
                $entity->amount = $amount;
                $entity->created_by = $updated_by;
                $entity->updated_by = $updated_by;

                $mAllowance->save($entity);
            }

            if ($sql->submissiontype == $mAbsent->Pengajuan_Pembatalan_Cuti && $line->isagree === "Y") {
                $balance = $mLeaveBalance->where([
                    'year'              => date("Y", strtotime($sql->startdate)),
                    'md_employee_id'    => $sql->md_employee_id
                ])->first();

                $refLeave = $this->where([$mAbsent->primaryKey => $sql->reference_id, 'date' => $line->date, 'isagree' => 'Y'])->first();

                $saldo = $balance->balance_amount;
                $carriedOverAmt = $balance->carried_over_amount;
                $carryOverValid = ($balance->carry_over_expiry_date && $line->date <= date('Y-m-d', strtotime($balance->carry_over_expiry_date)));
                $mainLeaveValid = ($balance->enddate && $line->date <= date('Y-m-d', strtotime($balance->enddate)));
                $year = date('Y', strtotime($line->date));

                $dataLeaveUsage = [];
                if ($carryOverValid) {
                    $entityBal = new \App\Entities\LeaveBalance();
                    $entityBal->md_employee_id = $sql->md_employee_id;
                    $entityBal->carried_over_amount = $carriedOverAmt + 1;
                    $entityBal->trx_leavebalance_id = $balance->trx_leavebalance_id;

                    $mLeaveBalance->save($entityBal);

                    $dataLeaveUsage[] = [
                        "record_id"         => $ID,
                        "table"             => $this->table,
                        "transactiondate"   => $line->date,
                        "transactiontype"   => 'C+',
                        "year"              => $year,
                        "amount"            => 1,
                        "md_employee_id"    => $sql->md_employee_id,
                        "isprocessed"       => "N",
                        "created_by"        => $updated_by,
                        "updated_by"        => $updated_by
                    ];
                } else if ($mainLeaveValid) {
                    $entityBal = new \App\Entities\LeaveBalance();
                    $entityBal->md_employee_id = $sql->md_employee_id;
                    $entityBal->balance_amount = $saldo + 1;
                    $entityBal->trx_leavebalance_id = $balance->trx_leavebalance_id;

                    $mLeaveBalance->save($entityBal);

                    $dataLeaveUsage[] = [
                        "record_id"         => $ID,
                        "table"             => $this->table,
                        "transactiondate"   => $line->date,
                        "transactiontype"   => 'C+',
                        "year"              => $year,
                        "amount"            => 1,
                        "md_employee_id"    => $sql->md_employee_id,
                        "isprocessed"       => "N",
                        "created_by"        => $updated_by,
                        "updated_by"        => $updated_by
                    ];
                }

                if ($refLeave) {
                    // Insert Reference LeaveCancel for Leave
                    $entity = new \App\Entities\AbsentDetail();
                    $entity->updated_by = $updated_by;
                    $entity->{$this->primaryKey} = $refLeave->trx_absent_detail_id;
                    $entity->isagree = "C";
                    $entity->ref_absent_detail_id = $ID;
                    $this->save($entity);
                }

                if ($dataLeaveUsage)
                    $mTransaction->builder->insertBatch($dataLeaveUsage);
            }
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function doAfterUpdate(array $rows)
    {
        $mAbsent = new M_Absent($this->request);
        $entity = new \App\Entities\Absent();

        try {
            $ID = isset($rows['id'][0]) ? $rows['id'][0] : $rows['id'];

            $line = $this->find($ID);
            $list = $this->where([
                'isagree'       => "H",
                'trx_absent_id' => $line->{$mAbsent->primaryKey}
            ])->first();

            if (is_null($list)) {
                $todayTime = date('Y-m-d H:i:s');
                $updatedBy = $rows['data']['updated_by'];

                $entity->setDocStatus("CO");
                $entity->setReceivedDate($todayTime);
                $entity->setAbsentId($line->{$mAbsent->primaryKey});
                $entity->setUpdatedBy($updatedBy);
                $mAbsent->save($entity);
            }

            if ($line->isagree === "Y")
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
        $result = [];

        $number = 1;

        foreach ($data as $row) :
            if (property_exists($row, "lineno"))
                $row->lineno = $number;

            if (!property_exists($row, "isagree"))
                $row->isagree = "H";

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
}

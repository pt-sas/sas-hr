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
        'created_by',
        'updated_by',
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\AbsentDetail';
    protected $allowCallbacks       = true;
    protected $beforeInsert         = [];
    protected $afterInsert          = [];
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
            trx_absent.documentno');

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
        $mHoliday = new M_Holiday($this->request);
        $mLeaveBalance = new M_LeaveBalance($this->request);
        $mEmpWork = new M_EmpWorkDay($this->request);
        $mWorkDetail = new M_WorkDetail($this->request);

        $amount = 0;

        $ID = $rows['id'][0];
        $updated_by = $rows['data']['updated_by'];
        $today = date('Y-m-d');
        $day = date('w');
        $entryTime = "08:00";

        $line = $this->find($ID);
        $sql = $mAbsent->where($mAbsent->primaryKey, $line->{$mAbsent->primaryKey})->first();

        try {
            $ruleDetail = null;
            if ($sql->submissiontype == $mAbsent->Pengajuan_Sakit) {
                $rule = $mRule->where([
                    'name'      => 'Sakit',
                    'isactive'  => 'Y'
                ])->first();

                if ($rule) {
                    $amount = $rule->condition ?: abs($rule->value);
                }
            }

            if ($sql->submissiontype == $mAbsent->Pengajuan_Alpa) {
                $rule = $mRule->where([
                    'name'      => 'Alpa',
                    'isactive'  => 'Y'
                ])->first();

                if ($rule) {
                    $amount = $rule->condition ?: abs($rule->value);

                    $ruleDetail = $mRuleDetail->where($mRule->primaryKey, $rule->md_rule_id)->findAll();

                    if ($ruleDetail) {
                        $balance = $mLeaveBalance->getBalance(['trx_leavebalance.md_employee_id' => $sql->md_employee_id]);
                        $saldo = $balance->amount;

                        foreach ($ruleDetail as $detail) {
                            if ($saldo != 0) {
                                if ($detail->name === "Sanksi Alpa Cuti") {
                                    $entityBal = new \App\Entities\LeaveBalance();
                                    $tkh = $detail->value;

                                    $calculate = $saldo + $tkh;

                                    if ($tkh != 0 && $line->isagree === 'Y') {
                                        if ($calculate > 0) {
                                            $entityBal->record_id = $sql->{$mAbsent->primaryKey};
                                            $entityBal->table = $mAbsent->table;
                                            $entityBal->md_employee_id = $sql->md_employee_id;
                                            $entityBal->submissiondate = $line->date;
                                            $entityBal->amount = $tkh;

                                            $mLeaveBalance->save($entityBal);

                                            $amount = 0;
                                        } else if ($saldo != 0) {
                                            $entityBal->record_id = $sql->{$mAbsent->primaryKey};;
                                            $entityBal->table = $mAbsent->table;
                                            $entityBal->md_employee_id = $sql->md_employee_id;
                                            $entityBal->submissiondate = $line->date;
                                            $entityBal->amount = -$saldo;

                                            $mLeaveBalance->save($entityBal);

                                            //? Cek perbandingan dari calculate variable 
                                            if ($calculate == -1 || $calculate == 0)
                                                $amount = 0;
                                        }

                                        if ($calculate < -1) {
                                            $entity = new \App\Entities\AllowanceAtt();

                                            $rDetail = $mRuleDetail->where([
                                                'md_rule_id' => $rule->md_rule_id,
                                                'name'       => 'Sanksi Alpa No Cuti'
                                            ])->first();

                                            $tkh = abs($rDetail->value);
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
                                    $tkh = abs($detail->value);
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
                    }
                }
            }

            if ($sql->submissiontype == $mAbsent->Pengajuan_Ijin) {
                $rule = $mRule->where([
                    'name'      => 'Ijin',
                    'isactive'  => 'Y'
                ])->first();

                if ($rule) {
                    $amount = $rule->condition ?: abs($rule->value);

                    $ruleDetail = $mRuleDetail->where($mRule->primaryKey, $rule->md_rule_id)->findAll();

                    if ($ruleDetail) {
                        $balance = $mLeaveBalance->getBalance(['trx_leavebalance.md_employee_id' => $sql->md_employee_id]);
                        $saldo = $balance->amount;

                        foreach ($ruleDetail as $detail) {
                            if ($balance->amount != 0) {
                                if ($detail->name === "Sanksi Ijin Cuti") {
                                    $entityBal = new \App\Entities\LeaveBalance();
                                    $tkh = $detail->value;

                                    $calculate = $saldo + $tkh;

                                    if ($tkh != 0 && $line->isagree === 'Y') {
                                        if ($calculate > 0) {
                                            $entityBal->record_id = $sql->{$mAbsent->primaryKey};
                                            $entityBal->table = $mAbsent->table;
                                            $entityBal->md_employee_id = $sql->md_employee_id;
                                            $entityBal->submissiondate = $line->date;
                                            $entityBal->amount = $tkh;

                                            $mLeaveBalance->save($entityBal);

                                            $amount = 0;
                                        } else if ($saldo != 0) {
                                            $entityBal->record_id = $sql->{$mAbsent->primaryKey};;
                                            $entityBal->table = $mAbsent->table;
                                            $entityBal->md_employee_id = $sql->md_employee_id;
                                            $entityBal->submissiondate = $line->date;
                                            $entityBal->amount = -$saldo;

                                            $mLeaveBalance->save($entityBal);

                                            if ($calculate == -1 || $calculate == 0)
                                                $amount = 0;
                                        }

                                        if ($calculate < -1) {
                                            $entity = new \App\Entities\AllowanceAtt();

                                            $rDetail = $mRuleDetail->where([
                                                'md_rule_id' => $rule->md_rule_id,
                                                'name'       => 'Sanksi Ijin No Cuti'
                                            ])->first();

                                            $tkh = abs($rDetail->value);

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
                                    $tkh = abs($detail->value);

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
                    }
                }
            }

            if ($sql->submissiontype == $mAbsent->Pengajuan_Tugas_Kantor) {
                $rule = $mRule->where([
                    'name'      => 'Tugas Kantor 1 Hari',
                    'isactive'  => 'Y'
                ])->first();

                if ($rule) {
                    $amount = $rule->condition ?: abs($rule->value);
                }
            }

            if ($sql->submissiontype == $mAbsent->Pengajuan_Tugas_Kantor_setengah_Hari) {
                $rule = $mRule->where([
                    'name'      => 'Tugas Kantor Setengah Hari',
                    'isactive'  => 'Y'
                ])->first();

                if ($rule) {
                    $amount = $rule->condition ?: abs($rule->value);
                }
            }

            if ($sql->submissiontype == $mAbsent->Pengajuan_Lupa_Absen_Masuk) {
                $rule = $mRule->where([
                    'name'      => 'Lupa Absen Masuk',
                    'isactive'  => 'Y'
                ])->first();

                if ($rule) {
                    $amount = $rule->condition ?: abs($rule->value);
                }
            }

            if ($sql->submissiontype == $mAbsent->Pengajuan_Lupa_Absen_Pulang) {
                $rule = $mRule->where([
                    'name'      => 'Lupa Absen Pulang',
                    'isactive'  => 'Y'
                ])->first();

                if ($rule) {
                    $amount = $rule->condition ?: abs($rule->value);
                }
            }

            if ($sql->submissiontype == $mAbsent->Pengajuan_Datang_Terlambat) {
                $rule = $mRule->where([
                    'name'      => 'Datang Terlambat',
                    'isactive'  => 'Y'
                ])->first();

                if ($rule) {
                    $amount = $rule->condition ?: abs($rule->value);

                    $ruleDetail = $mRuleDetail->where($mRule->primaryKey, $rule->md_rule_id)->findAll();

                    if ($ruleDetail) {
                        //TODO : Get work day employee
                        $workDay = $mEmpWork->where([
                            'md_employee_id'    => $sql->md_employee_id,
                            'validfrom <='      => $today
                        ])->orderBy('validfrom', 'ASC')->first();

                        if (is_null($workDay)) {
                            $workHour = convertToMinutes($entryTime);
                        } else {
                            $day = strtoupper(formatDay_idn($day));

                            //TODO: Get Work Detail by day 
                            $work = null;

                            $whereClause = "md_work_detail.isactive = 'Y'";
                            $whereClause .= " AND md_employee_work.md_employee_id = $sql->md_employee_id";
                            $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
                            $whereClause .= " AND md_day.name = '$day'";
                            $work = $mWorkDetail->getWorkDetail($whereClause)->getRow();

                            if (is_null($work)) {
                                $workHour = convertToMinutes($entryTime);
                            } else {
                                $workHour = convertToMinutes($work->startwork);
                            }
                        }

                        $workTime = convertToMinutes($sql->startdate);

                        foreach ($ruleDetail as $detail) {
                            if (($detail->name === "Terlambat 1/2 Hari" || $detail->name === "Terlambat") && getOperationResult($workTime, ($workHour + $detail->condition), $detail->operation)) {
                                $amount = abs($detail->value);
                            }
                        }
                    }
                }
            }

            if ($sql->submissiontype == $mAbsent->Pengajuan_Pulang_Cepat) {
                $rule = $mRule->where([
                    'name'      => 'Pulang Cepat',
                    'isactive'  => 'Y'
                ])->first();

                if ($rule) {
                    $amount = $rule->condition ?: abs($rule->value);

                    $ruleDetail = $mRuleDetail->where($mRule->primaryKey, $rule->md_rule_id)->findAll();

                    if ($ruleDetail) {
                        //TODO : Get work day employee
                        $workDay = $mEmpWork->where([
                            'md_employee_id'    => $sql->md_employee_id,
                            'validfrom <='      => $today
                        ])->orderBy('validfrom', 'ASC')->first();

                        if (is_null($workDay)) {
                            $workHour = convertToMinutes($entryTime);
                        } else {
                            $day = strtoupper(formatDay_idn($day));

                            //TODO: Get Work Detail by day 
                            $work = null;

                            $whereClause = "md_work_detail.isactive = 'Y'";
                            $whereClause .= " AND md_employee_work.md_employee_id = $sql->md_employee_id";
                            $whereClause .= " AND md_work.md_work_id = $workDay->md_work_id";
                            $whereClause .= " AND md_day.name = '$day'";
                            $work = $mWorkDetail->getWorkDetail($whereClause)->getRow();

                            if (is_null($work)) {
                                $workHour = convertToMinutes($entryTime);
                            } else {
                                $workHour = convertToMinutes($work->startwork);
                            }
                        }

                        $workTime = convertToMinutes($sql->startdate);

                        foreach ($ruleDetail as $detail) {
                            if (($detail->name === "Pulang Cepat 1/2 Hari" || $detail->name === "Pulang Cepat") && getOperationResult($workTime, ($workHour + $detail->condition), $detail->operation)) {
                                $amount = abs($detail->value);
                            }
                        }
                    }
                }
            }

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
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }
}

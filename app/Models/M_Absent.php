<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Absent extends Model
{
    protected $table                = 'trx_absent';
    protected $primaryKey           = 'trx_absent_id';
    protected $allowedFields        = [
        'documentno',
        'md_employee_id',
        'nik',
        'md_branch_id',
        'md_division_id',
        'submissiondate',
        'receiveddate',
        'necessary',
        'submissiontype',
        'startdate',
        'enddate',
        'reason',
        'docstatus',
        'image',
        'isapproved',
        'approveddate',
        'sys_wfscenario_id',
        'created_by',
        'updated_by',
        'md_leavetype_id',
        'image2',
        'image3'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\Absent';
    protected $allowCallbacks       = true;
    protected $beforeInsert         = [];
    protected $afterInsert          = [];
    protected $beforeUpdate         = [];
    protected $afterUpdate          = ['createDetail'];
    protected $beforeDelete         = [];
    protected $afterDelete          = [];
    protected $column_order         = [
        '', // Hide column
        '', // Number column
        'md_branch.value',
        'md_branch.name',
        'md_branch.address',
        'md_employee.name',
        'md_branch.phone',
        'md_branch.isactive'
    ];
    protected $column_search        = [
        'md_branch.value',
        'md_branch.name',
        'md_branch.address',
        'md_employee.name',
        'md_branch.phone',
        'md_branch.isactive'
    ];
    protected $order                = ['documentno' => 'ASC'];
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

    public function getSelect()
    {
        $sql = $this->table . '.*,
                md_employee.value as employee,
                md_employee.fullname as employee_fullname,
                md_branch.name as branch,
                md_division.name as division,
                sys_ref_detail.name as necessarytype,
                sys_user.name as createdby,
                md_leavetype.name as leavetype';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_employee', 'md_employee.md_employee_id = ' . $this->table . '.md_employee_id', 'left'),
            $this->setDataJoin('md_branch', 'md_branch.md_branch_id = ' . $this->table . '.md_branch_id', 'left'),
            $this->setDataJoin('md_division', 'md_division.md_division_id = ' . $this->table . '.md_division_id', 'left'),
            $this->setDataJoin('sys_reference', 'sys_reference.name = "NecessaryType"', 'left'),
            $this->setDataJoin('sys_ref_detail', 'sys_ref_detail.value = ' . $this->table . '.necessary AND sys_reference.sys_reference_id = sys_ref_detail.sys_reference_id', 'left'),
            $this->setDataJoin('sys_user', 'sys_user.sys_user_id = ' . $this->table . '.created_by', 'left'),
            $this->setDataJoin('md_leavetype', 'md_leavetype.md_leavetype_id = ' . $this->table . '.md_leavetype_id', 'left')
        ];

        return $sql;
    }

    public function getSelectDetail()
    {
        $sql = $this->table . '.*,
                md_employee.value as employee,
                md_employee.fullname as employee_fullname,
                md_branch.name as branch,
                md_division.name as division,
                trx_absent_detail.trx_absent_detail_id,
                trx_absent_detail.isagree,
                trx_absent_detail.date,
                md_leavetype.name as leavetype';

        return $sql;
    }

    public function getJoinDetail()
    {
        $sql = [
            $this->setDataJoin('trx_absent_detail', 'trx_absent_detail.trx_absent_id = ' . $this->table . '.trx_absent_id', 'left'),
            $this->setDataJoin('md_employee', 'md_employee.md_employee_id = ' . $this->table . '.md_employee_id', 'left'),
            $this->setDataJoin('md_branch', 'md_branch.md_branch_id = ' . $this->table . '.md_branch_id', 'left'),
            $this->setDataJoin('md_division', 'md_division.md_division_id = ' . $this->table . '.md_division_id', 'left'),
            $this->setDataJoin('md_leavetype', 'md_leavetype.md_leavetype_id = ' . $this->table . '.md_leavetype_id', 'left'),
        ];

        return $sql;
    }

    private function setDataJoin($tableJoin, $columnJoin, $typeJoin = "inner")
    {
        return [
            "tableJoin" => $tableJoin,
            "columnJoin" => $columnJoin,
            "typeJoin" => $typeJoin
        ];
    }

    public function getInvNumber($field, $where, $post)
    {
        $year = date("Y", strtotime($post['submissiondate']));
        $month = date("m", strtotime($post['submissiondate']));

        $this->builder->select('MAX(RIGHT(documentno,4)) AS documentno');
        $this->builder->where("DATE_FORMAT(submissiondate, '%m')", $month);
        $this->builder->where($field, $where);
        $sql = $this->builder->get();

        $code = "";
        if ($sql->getNumRows() > 0) {
            foreach ($sql->getResult() as $row) {
                $doc = ((int)$row->documentno + 1);
                $code = sprintf("%04s", $doc);
            }
        } else {
            $code = "0001";
        }
        $first = $post['necessary'];

        $prefix = $first . "/" . $year . "/" . $month . "/" . $code;

        return $prefix;
    }

    public function createDetail(array $rows)
    {
        $mAbsentDetail = new M_AbsentDetail($this->request);
        $mHoliday = new M_Holiday($this->request);

        $sql = $this->find($rows['id'][0]);
        $line = $mAbsentDetail->where($this->primaryKey, $rows['id'][0])->first();

        if ($sql->getIsApproved() === 'Y' && $sql->docstatus === "IP" && is_null($line)) {
            $holiday = $mHoliday->getHolidayDate();

            $date_range = getDatesFromRange($sql->getStartDate(), $sql->getEndDate(), $holiday);

            $data = [];
            $number = 0;
            foreach ($date_range as $date) :
                $row = [];

                $number++;

                $row[$this->primaryKey] = $rows['id'][0];
                $row['date'] = $date;
                $row['lineno'] = $number;
                $row['isagree'] = 'H';
                $row['created_by'] = $rows['data']['updated_by'];
                $row['updated_by'] = $rows['data']['updated_by'];
                $data[] = $row;
            endforeach;

            $mAbsentDetail->builder->insertBatch($data);
        }

        $this->createAllowance($rows);
    }

    public function createAllowance(array $rows)
    {
        $mRule = new M_Rule($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);
        $mAllowance = new M_AllowanceAtt($this->request);
        $mAbsentDetail = new M_AbsentDetail($this->request);
        $mHoliday = new M_Holiday($this->request);

        $amount = 0;

        $ID = $rows['id'][0];
        $sql = $this->find($ID);
        $line = $mAbsentDetail->where($this->primaryKey, $ID)->first();

        if ($sql->getIsApproved() === 'Y' && $sql->docstatus === "IP" && is_null($line)) {
            $holiday = $mHoliday->getHolidayDate();

            $date_range = getDatesFromRange($sql->getStartDate(), $sql->getEndDate(), $holiday);

            $data = [];
            $number = 0;
            foreach ($date_range as $date) :
                $row = [];

                $number++;

                $row[$this->primaryKey] = $ID;
                $row['date'] = $date;
                $row['lineno'] = $number;
                $row['isagree'] = 'H';
                $row['created_by'] = $rows['data']['updated_by'];
                $row['updated_by'] = $rows['data']['updated_by'];
                $data[] = $row;
            endforeach;

            $mAbsentDetail->builder->insertBatch($data);
        }

        if ($sql->docstatus === "CO") {
            if ($sql->submissiontype === "sakit") {
                $rule = $mRule->where([
                    'name'      => 'Sakit',
                    'isactive'  => 'Y'
                ])->first();

                if ($rule) {
                    if ($rule->condition === "")
                        $amount = abs($rule->value);

                    $range = $mAbsentDetail->where([
                        'trx_absent_id' => $sql->trx_absent_id,
                        'isagree'       => 'Y'
                    ])->orderBy('lineno', 'ASC')->findAll();

                    $arr = [];

                    if ($amount != 0 && $range) {
                        foreach ($range as $row) {
                            $arr[] = [
                                "record_id"         => $ID,
                                "table"             => $this->table,
                                "submissiontype"    => $sql->submissiontype,
                                "submissiondate"    => $row->date,
                                "md_employee_id"    => $sql->md_employee_id,
                                "amount"            => $amount,
                                "created_by"        => $rows['data']['updated_by'],
                                "updated_by"        => $rows['data']['updated_by']
                            ];
                        }

                        $mAllowance->builder->insertBatch($arr);
                    }
                }
            }

            if ($sql->submissiontype === "lupa absen masuk") {
                $rule = $mRule->where([
                    'name'      => 'Lupa Absen Masuk',
                    'isactive'  => 'Y'
                ])->first();

                if ($rule) {
                    $range = $mAbsentDetail->where([
                        'trx_absent_id' => $sql->trx_absent_id,
                        'isagree'       => 'Y'
                    ])->orderBy('lineno', 'ASC')->findAll();

                    $amount = abs($rule->value);

                    if ($amount != 0 && $range) {
                        $arr[] = [
                            "record_id"         => $ID,
                            "table"             => $this->table,
                            "submissiontype"    => $sql->submissiontype,
                            "submissiondate"    => $row->date,
                            "md_employee_id"    => $sql->md_employee_id,
                            "amount"            => $amount,
                            "created_by"        => $rows['data']['updated_by'],
                            "updated_by"        => $rows['data']['updated_by']
                        ];

                        $mAllowance->builder->insertBatch($arr);
                    }
                }
            }

            if ($sql->submissiontype === "lupa absen pulang") {
                $rule = $mRule->where([
                    'name'      => 'Lupa Absen Pulang',
                    'isactive'  => 'Y'
                ])->first();

                if ($rule) {
                    $range = $mAbsentDetail->where([
                        'trx_absent_id' => $sql->trx_absent_id,
                        'isagree'       => 'Y'
                    ])->orderBy('lineno', 'ASC')->findAll();

                    $amount = abs($rule->value);

                    if ($amount != 0 && $range) {
                        $arr[] = [
                            "record_id"         => $ID,
                            "table"             => $this->table,
                            "submissiontype"    => $sql->submissiontype,
                            "submissiondate"    => $row->date,
                            "md_employee_id"    => $sql->md_employee_id,
                            "amount"            => $amount,
                            "created_by"        => $rows['data']['updated_by'],
                            "updated_by"        => $rows['data']['updated_by']
                        ];

                        $mAllowance->builder->insertBatch($arr);
                    }
                }
            }

            if ($sql->submissiontype === "datang terlambat") {
                $rule = $mRule->where('name', 'Terlambat')->first();

                if ($rule) {
                    $ruleDetail = $mRuleDetail->where('md_rule_id', $rule->md_rule_id)->findAll();

                    $jamMasuk = convertToMinutes(format_time('08:00'));
                    $pagi = ($jamMasuk + $ruleDetail[0]->condition);
                    $siang = ($jamMasuk + $ruleDetail[1]->condition);
                    $jam = convertToMinutes(format_time($sql->startdate));

                    if ($rule->isdetail === 'Y') {
                        if (getOperationResult($jam, $siang, $ruleDetail[1]->operation) === true) {
                            $amount = abs($ruleDetail[1]->value);
                        } else if (getOperationResult($jam, $pagi, $ruleDetail[0]->operation) === true) {
                            $amount = abs($ruleDetail[0]->value);
                        }
                    }

                    if ($amount != 0) {
                        $arr[] = [
                            "record_id"         => $ID,
                            "table"             => $this->table,
                            "submissiontype"    => $sql->submissiontype,
                            "submissiondate"    => $row->date,
                            "md_employee_id"    => $sql->md_employee_id,
                            "amount"            => $amount,
                            "created_by"        => $rows['data']['updated_by'],
                            "updated_by"        => $rows['data']['updated_by']
                        ];

                        $mAllowance->builder->insertBatch($arr);
                    }
                }
            }

            if ($sql->submissiontype === "pulang cepat") {
                $rule = $mRule->where('name', 'Pulang Cepat')->find();

                if ($rule) {
                    $ruleDetail = $mRuleDetail->where('md_rule_id = ' . $rule[0]->md_rule_id)->find();

                    $jamMasuk = convertToMinutes(format_time('08:00'));
                    $sore = ($jamMasuk + $ruleDetail[0]->condition);
                    $siang = ($jamMasuk + $ruleDetail[1]->condition);
                    $jam = convertToMinutes(format_time($sql->startdate));

                    if ($rule[0]->isdetail === 'Y') {
                        if (getOperationResult($jam, $jamMasuk, $ruleDetail[0]->operation) === true) {
                            $amount = 0;
                        } else if (getOperationResult($jam, $siang, $ruleDetail[1]->operation) === true) {
                            $amount = abs($ruleDetail[1]->value);
                        } else if (getOperationResult($jam, $sore, $ruleDetail[0]->operation) === true) {
                            $amount = abs($ruleDetail[0]->value);
                        }
                    }

                    if ($amount != 0) {
                        $arr[] = [
                            "record_id"         => $ID,
                            "table"             => $this->table,
                            "submissiontype"    => $sql->submissiontype,
                            "submissiondate"    => $row->date,
                            "md_employee_id"    => $sql->md_employee_id,
                            "amount"            => $amount,
                            "created_by"        => $rows['data']['updated_by'],
                            "updated_by"        => $rows['data']['updated_by']
                        ];

                        $mAllowance->builder->insertBatch($arr);
                    }
                }
            }

            if ($sql->submissiontype === "alpa") {
                $rule = $mRule->where([
                    'name'      => 'Alpa',
                    'isactive'  => 'Y'
                ])->first();

                if ($rule) {
                    if ($rule->condition === "")
                        $amount = abs($rule->value);

                    $range = $mAbsentDetail->where([
                        'trx_absent_id' => $sql->trx_absent_id,
                        'isagree'       => 'Y'
                    ])->orderBy('lineno', 'ASC')->findAll();

                    if ($amount != 0 && $range) {
                        foreach ($range as $row) {
                            $arr[] = [
                                "record_id"         => $ID,
                                "table"             => $this->table,
                                "submissiontype"    => $sql->submissiontype,
                                "submissiondate"    => $row->date,
                                "md_employee_id"    => $sql->md_employee_id,
                                "amount"            => $amount,
                                "created_by"        => $rows['data']['updated_by'],
                                "updated_by"        => $rows['data']['updated_by']
                            ];
                        }

                        $mAllowance->builder->insertBatch($arr);
                    }
                }
            }

            if ($sql->submissiontype === "ijin") {
                $rule = $mRule->where([
                    'name'      => 'Ijin',
                    'isactive'  => 'Y'
                ])->first();

                if ($rule) {
                    $ruleDetail = $mRuleDetail->where('md_rule_id = ' . $rule->md_rule_id)->findAll();

                    if ($rule->condition === "")
                        $amount = abs($rule->value);

                    $range = $mAbsentDetail->where([
                        'trx_absent_id' => $sql->trx_absent_id,
                        'isagree'       => 'Y'
                    ])->orderBy('lineno', 'ASC')->findAll();

                    $arr = [];

                    if ($amount != 0 && $range) {
                        foreach ($range as $row) {
                            $arr[] = [
                                "record_id"         => $ID,
                                "table"             => $this->table,
                                "submissiontype"    => $sql->submissiontype,
                                "submissiondate"    => $row->date,
                                "md_employee_id"    => $sql->md_employee_id,
                                "amount"            => $amount,
                                "created_by"        => $rows['data']['updated_by'],
                                "updated_by"        => $rows['data']['updated_by']
                            ];
                        }

                        foreach ($ruleDetail as $detail) {
                            if ($detail->name === "Sanksi Ijin No Cuti") {
                                $amount = abs($detail->value);

                                foreach ($range as $row) {
                                    $arr[] = [
                                        "record_id"         => $ID,
                                        "table"             => $this->table,
                                        "submissiontype"    => $sql->submissiontype,
                                        "submissiondate"    => $row->date,
                                        "md_employee_id"    => $sql->md_employee_id,
                                        "amount"            => $amount,
                                        "created_by"        => $rows['data']['updated_by'],
                                        "updated_by"        => $rows['data']['updated_by']
                                    ];
                                }
                            }
                        }

                        $mAllowance->builder->insertBatch($arr);
                    }
                }
            }

            if ($sql->submissiontype === "tugas kantor") {
                $rule = $mRule->where([
                    'name'      => 'Tugas Kantor 1 Hari',
                    'isactive'  => 'Y'
                ])->first();

                if ($rule) {
                    if ($rule->condition === "")
                        $amount = abs($rule->value);

                    $range = $mAbsentDetail->where([
                        'trx_absent_id' => $sql->trx_absent_id,
                        'isagree'       => 'Y'
                    ])->orderBy('lineno', 'ASC')->findAll();

                    $arr = [];

                    if ($amount != 0 && $range) {
                        foreach ($range as $row) {
                            $arr[] = [
                                "record_id"         => $ID,
                                "table"             => $this->table,
                                "submissiontype"    => $sql->submissiontype,
                                "submissiondate"    => $row->date,
                                "md_employee_id"    => $sql->md_employee_id,
                                "amount"            => $amount,
                                "created_by"        => $rows['data']['updated_by'],
                                "updated_by"        => $rows['data']['updated_by']
                            ];
                        }

                        $mAllowance->builder->insertBatch($arr);
                    }
                }
            }
        }
    }
}

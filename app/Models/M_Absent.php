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
        'md_leavetype_id'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\Absent';
    protected $allowCallbacks       = true;
    protected $beforeInsert         = [];
    protected $afterInsert          = [];
    protected $beforeUpdate         = [];
    protected $afterUpdate          = [];
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

    private function setDataJoin($tableJoin, $columnJoin, $typeJoin = "inner")
    {
        return [
            "tableJoin" => $tableJoin,
            "columnJoin" => $columnJoin,
            "typeJoin" => $typeJoin
        ];
    }

    public function getInvNumber($field, $where, $form)
    {
        $post = $this->request->getPost();

        $year = date("Y", strtotime($post['submissiondate']));
        $month = date("m", strtotime($post['submissiondate']));
        $post["necessary"] = $form;

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
        $first = $post["necessary"];

        $prefix = $first . "/" . $year . "/" . $month . "/" . $code;

        return $prefix;
    }

    public function createAllowance(array $rows)
    {
        $mRule = new M_Rule($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);
        $mAllowance = new M_AllowanceAtt($this->request);

        $amount = 0;

        $sql = $this->find($rows['id'][0]);

        if ($sql->docstatus === "CO") {
            if ($sql->submissiontype === "sakit") {
                $rule = $mRule->where(['name' => 'Sakit', 'isactive' => 'Y'])->first();

                if ($rule->condition === "")
                    $amount = abs($rule->value);

                $range = getDatesFromRange($sql->startdate, $sql->enddate);

                $arr = [];

                if ($amount != 0) {
                    foreach ($range as $date) {
                        $arr[] = [
                            "record_id"         => $rows['id'][0],
                            "table"             => $this->table,
                            "submissiontype"    => $sql->submissiontype,
                            "submissiondate"    => $date,
                            "md_employee_id"    => $sql->md_employee_id,
                            "amount"            => $amount,
                            "created_by"        => $rows['data']['updated_by'],
                            "updated_by"        => $rows['data']['updated_by']
                        ];
                    }

                    $mAllowance->builder->insertBatch($arr);
                }
            }

            if ($sql->submissiontype === "lupa absen masuk") {
                $rule = $mRule->where('name', 'Lupa Absen')->first();
                $ruleDetail = $mRuleDetail->where([
                    'md_rule_id'    => $rule->md_rule_id,
                    'name'          => 'Lupa Absen Masuk'
                ])->first();

                $amount = abs($ruleDetail->value);

                if ($amount != 0) {
                    $arr[] = [
                        "record_id"         => $rows['id'][0],
                        "table"             => $this->table,
                        "submissiontype"    => $sql->submissiontype,
                        "submissiondate"    => $date,
                        "md_employee_id"    => $sql->md_employee_id,
                        "amount"            => $amount,
                        "created_by"        => $rows['data']['updated_by'],
                        "updated_by"        => $rows['data']['updated_by']
                    ];

                    $mAllowance->builder->insertBatch($arr);
                }
            }

            if ($sql->submissiontype === "lupa absen pulang") {
                $rule = $mRule->where('name', 'Lupa Absen')->first();
                $ruleDetail = $mRuleDetail->where([
                    'md_rule_id' => $rule->md_rule_id,
                    'name' => 'Lupa Absen Pulang'
                ])->first();

                $amount = abs($ruleDetail->value);

                if ($amount != 0) {
                    $arr[] = [
                        "record_id"         => $rows['id'][0],
                        "table"             => $this->table,
                        "submissiontype"    => $sql->submissiontype,
                        "submissiondate"    => $date,
                        "md_employee_id"    => $sql->md_employee_id,
                        "amount"            => $amount,
                        "created_by"        => $rows['data']['updated_by'],
                        "updated_by"        => $rows['data']['updated_by']
                    ];

                    $mAllowance->builder->insertBatch($arr);
                }
            }

            if ($sql->submissiontype === "datang terlambat") {
                $rule = $mRule->where('name', 'Terlambat')->first();
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
                        "record_id"         => $rows['id'][0],
                        "table"             => $this->table,
                        "submissiontype"    => $sql->submissiontype,
                        "submissiondate"    => $date,
                        "md_employee_id"    => $sql->md_employee_id,
                        "amount"            => $amount,
                        "created_by"        => $rows['data']['updated_by'],
                        "updated_by"        => $rows['data']['updated_by']
                    ];

                    $mAllowance->builder->insertBatch($arr);
                }
            }

            if ($sql->submissiontype === "pulang cepat") {
                $rule = $mRule->where('name', 'Pulang Cepat')->find();
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
                        "record_id"         => $rows['id'][0],
                        "table"             => $this->table,
                        "submissiontype"    => $sql->submissiontype,
                        "submissiondate"    => $date,
                        "md_employee_id"    => $sql->md_employee_id,
                        "amount"            => $amount,
                        "created_by"        => $rows['data']['updated_by'],
                        "updated_by"        => $rows['data']['updated_by']
                    ];

                    $mAllowance->builder->insertBatch($arr);
                }
            }
        }
    }
}

<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Memo extends Model
{
    protected $table                = 'trx_hr_memo';
    protected $primaryKey           = 'trx_hr_memo_id';
    protected $allowedFields        = [
        'documentno',
        'md_employee_id',
        'nik',
        'md_branch_id',
        'md_division_id',
        'submissiondate',
        'memodate',
        'memotype',
        'memocriteria',
        'memocontent',
        'totaldays',
        'description',
        'docstatus',
        'isapproved',
        'approveddate',
        'sys_wfscenario_id',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\Memo';
    protected $allowCallbacks       = true;
    protected $beforeInsert         = [];
    protected $afterInsert          = ['createAllowance'];
    protected $beforeUpdate         = [];
    protected $afterUpdate          = [];
    protected $beforeDelete         = [];
    protected $afterDelete          = [];
    protected $column_order         = [
        '', // Hide column
        '', // Number column
        'trx_hr_memo.documentno',
        'md_employee.fullname',
        'md_employee.nik',
        'md_branch.name',
        'md_division.name',
        'trx_hr_memo.memodate',
        'trx_hr_memo.memocriteria',
        'trx_hr_memo.memocontent',
        'trx_hr_memo.docstatus',
        'sys_user.name'
    ];
    protected $column_search        = [
        'trx_hr_memo.documentno',
        'md_employee.fullname',
        'md_employee.nik',
        'md_branch.name',
        'md_division.name',
        'trx_hr_memo.memodate',
        'trx_hr_memo.memocriteria',
        'trx_hr_memo.memocontent',
        'trx_hr_memo.docstatus',
        'sys_user.name'
    ];
    protected $order                = ['created_at' => 'DESC'];
    protected $request;
    protected $db;
    protected $builder;
    /** Pengajuan Memo SDM */
    protected $Pengajuan_Memo_SDM    = 100015;

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
                sys_ref_detail.name as criteria,
                sys_user.name as createdby';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_employee', 'md_employee.md_employee_id = ' . $this->table . '.md_employee_id', 'left'),
            $this->setDataJoin('md_branch', 'md_branch.md_branch_id = ' . $this->table . '.md_branch_id', 'left'),
            $this->setDataJoin('md_division', 'md_division.md_division_id = ' . $this->table . '.md_division_id', 'left'),
            $this->setDataJoin('sys_reference', 'sys_reference.name = "MemoCriteria"', 'left'),
            $this->setDataJoin('sys_ref_detail', 'sys_ref_detail.value = ' . $this->table . '.memocriteria AND sys_reference.sys_reference_id = sys_ref_detail.sys_reference_id', 'left'),
            $this->setDataJoin('sys_user', 'sys_user.sys_user_id = ' . $this->table . '.created_by', 'left')
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

    public function getInvNumber($post)
    {
        $year = date("Y", strtotime($post['memodate']));
        $month = date("m", strtotime($post['memodate']));

        $this->builder->select('MAX(RIGHT(documentno,4)) AS documentno');
        $this->builder->where("DATE_FORMAT(memodate, '%m')", $month);
        // $this->builder->where($field, $where);
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

    public function getSelectList()
    {
        $sql = 'v_attendance_submission.*,
        md_employee.fullname,
        md_employee.nik,
        md_branch.name as branch,
        md_division.name as division';

        return $sql;
    }

    public function getJoinList()
    {
        $sql = [

            $this->setDataJoin('md_employee', 'md_employee.md_employee_id = v_attendance_submission.md_employee_id', 'left'),
            $this->setDataJoin('md_employee_branch', 'md_employee_branch.md_employee_id = md_employee.md_employee_id', 'left'),
            $this->setDataJoin('md_branch', 'md_branch.md_branch_id = md_employee_branch.md_branch_id', 'left'),
            $this->setDataJoin('md_employee_division', 'md_employee_division.md_employee_id = md_employee.md_employee_id', 'left'),
            $this->setDataJoin('md_division', 'md_division.md_division_id = md_employee_division.md_division_id', 'left')
        ];

        return $sql;
    }

    public function createAllowance(array $rows)
    {
        $mRule = new M_Rule($this->request);
        $mRuleDetail = new M_RuleDetail($this->request);
        $mAllowance = new M_AllowanceAtt($this->request);
        $mEmployee = new M_Employee($this->request);

        $amount = 0;

        $ID = isset($rows['id'][0]) ? $rows['id'][0] : $rows['id'];
        $memoDate = $rows['data']['memodate'];
        $submissionDate = $rows['data']['submissiondate'];
        $criteria = $rows['data']['memocriteria'];
        $totalDays = $rows['data']['totaldays'];
        $employeeID = $rows['data']['md_employee_id'];

        $startOfMonth = date('Y-m-d', strtotime('first day of this month', strtotime($memoDate)));
        $lastMonth = date('Y-m', strtotime('-1 month', strtotime($startOfMonth)));
        $twoMonthAgo = date('Y-m', strtotime('-2 month', strtotime($startOfMonth)));

        $memoTwoMonthAgo = $this->where([
            'docstatus'                             => 'CO',
            'date_format(memodate, "%Y-%m")'        => $twoMonthAgo,
            'md_employee_id'                        => $employeeID
        ])->first();

        $memoLastMonthAgo = $this->where([
            'docstatus'                             => 'CO',
            'date_format(memodate, "%Y-%m")'        => $lastMonth,
            'md_employee_id'                        => $employeeID
        ])->first();

        $rowEmployee = $mEmployee->find($employeeID);

        if ($criteria === "kehadiran") {
            $rule = $mRule->where([
                'name'      => 'Datang Terlambat',
                'isactive'  => 'Y'
            ])->first();
        } else if ($criteria === "ijin") {
            $rule = $mRule->where([
                'name'      => 'Ijin',
                'isactive'  => 'Y'
            ])->first();
        } else if ($criteria === "alpa") {
            $rule = $mRule->where([
                'name'      => 'Alpa',
                'isactive'  => 'Y'
            ])->first();
        }

        if ($rule) {
            $ruleDetail = $mRuleDetail->where($mRule->primaryKey, $rule->md_rule_id)->findAll();

            if ($ruleDetail) {
                foreach ($ruleDetail as $detail) {
                    if ($detail->name === "Permanent" && $rowEmployee->getStatusId() == 100001 && getOperationResult($totalDays, $detail->condition, $detail->operation)) {
                        $amount = $detail->value;

                        if ($criteria !== "alpa")
                            if (getOperationResult($totalDays, $detail->condition, $detail->operation) && $memoTwoMonthAgo && $memoLastMonthAgo) {
                                $amount *= 3;
                            } else if (getOperationResult($totalDays, $detail->condition, $detail->operation) && is_null($memoTwoMonthAgo) && $memoLastMonthAgo) {
                                $amount *= 2;
                            }
                    } else if ($detail->name === "Probation" && $rowEmployee->getStatusId() == 100002 && getOperationResult($totalDays, $detail->condition, $detail->operation)) {
                        $amount = $detail->value;
                    }
                }
            }
        }

        if ($amount != 0) {
            $mAllowance->insertAllowance($ID, $this->table, 'A-', $submissionDate, $this->Pengajuan_Memo_SDM, $employeeID, $amount, $rows['data']['created_by']);
        }

        return $rows;
    }
}
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
        'memo_level',
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
        $mRuleValue = new M_RuleValue($this->request);
        $mAllowance = new M_AllowanceAtt($this->request);

        $amount = 0;

        $ID = isset($rows['id'][0]) ? $rows['id'][0] : $rows['id'];
        $trx = $this->find($ID);
        $submissionDate = $rows['data']['submissiondate'];
        $employeeID = $rows['data']['md_employee_id'];

        $ruleValue = $mRuleValue->where('name', "Memo SDM {$trx->memo_level}")->first();
        $amount = $ruleValue->value;

        if ($amount != 0) {
            $mAllowance->insertAllowance($ID, $this->table, 'A-', $submissionDate, $this->Pengajuan_Memo_SDM, $employeeID, $amount, $rows['data']['created_by']);
        }

        return $rows;
    }

    public function getMemoList($where)
    {
        $builder = $this->builder('v_attendance_submission');

        $builder->select("v_attendance_submission.*,
        md_employee.fullname,
        md_employee.nik");

        $builder->join('md_employee', 'md_employee.md_employee_id = v_attendance_submission.md_employee_id', 'left');

        $builder->where($where);

        return $builder->get();
    }
}
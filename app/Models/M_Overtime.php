<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Overtime extends Model
{
    protected $table                = 'trx_overtime';
    protected $primaryKey           = 'trx_overtime_id';
    protected $allowedFields        = [
        'documentno',
        'md_employee_id',
        'md_branch_id',
        'md_division_id',
        'submissiondate',
        'description',
        'docstatus',
        'isapproved',
        'startdate',
        'enddate',
        'approveddate',
        'sys_wfscenario_id',
        'created_by',
        'updated_by',
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\Overtime';
    protected $column_order         = [
        '', // Hide column
        '', // Number column
        'trx_overtime.documentno',
        'md_employee.fullname',
        'md_branch.name',
        'md_division.name',
        'trx_overtime.submissiondate',
        'trx_overtime.startdate',
        'trx_overtime.description',
        'trx_overtime.docstatus',
        'sys_user.name'
    ];
    protected $column_search        = [
        'trx_overtime.documentno',
        'md_employee.fullname',
        'md_branch.name',
        'md_division.name',
        'trx_overtime.submissiondate',
        'trx_overtime.startdate',
        'trx_overtime.description',
        'trx_overtime.docstatus',
        'sys_user.name'
    ];
    protected $order                = ['submissiondate' => 'DESC'];
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
                md_employee.nik as nik,
                md_branch.name as branch,
                sys_user.name as createdby,
                md_division.name as division';

        return $sql;
    }

    public function getSelectDetail()
    {
        $sql = $this->table . '.*,
        trx_overtime_detail.trx_overtime_detail_id,
        trx_overtime_detail.md_employee_id,
        md_employee.fullname as employee_name,
        md_employee.nik,
        trx_overtime_detail.startdate as startdate_line,
        trx_overtime_detail.enddate as enddate_line,
        trx_overtime_detail.description,
        trx_overtime_detail.overtime_balance,
        trx_overtime_detail.overtime_expense,
        trx_overtime_detail.total,
        trx_overtime_detail.status,
        md_branch.name as branch_name,
        md_division.name as division_name';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_employee', 'md_employee.md_employee_id = ' . $this->table . '.md_employee_id', 'left'),
            $this->setDataJoin('md_branch', 'md_branch.md_branch_id = ' . $this->table . '.md_branch_id', 'left'),
            $this->setDataJoin('md_division', 'md_division.md_division_id = ' . $this->table . '.md_division_id', 'left'),
            $this->setDataJoin('sys_user', 'sys_user.sys_user_id = ' . $this->table . '.created_by', 'left')
        ];

        return $sql;
    }

    public function getJoinDetail()
    {
        $sql = [
            $this->setDataJoin('md_branch', 'md_branch.md_branch_id = ' . $this->table . '.md_branch_id', 'left'),
            $this->setDataJoin('md_division', 'md_division.md_division_id = ' . $this->table . '.md_division_id', 'left'),
            $this->setDataJoin('trx_overtime_detail', 'trx_overtime_detail.trx_overtime_id = ' . $this->table . '.trx_overtime_id', 'left'),
            $this->setDataJoin('md_employee', 'md_employee.md_employee_id = trx_overtime_detail.md_employee_id', 'left')
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

    public function getInvNumber()
    {
        $post = $this->request->getPost();

        $year = date("Y", strtotime($post['submissiondate']));
        $month = date("m", strtotime($post['submissiondate']));

        $this->builder->select('MAX(RIGHT(documentno,4)) AS documentno');
        $this->builder->where("DATE_FORMAT(submissiondate, '%m')", $month);
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
        $first = 'LB';

        $prefix = $first . "/" . $year . "/" . $month . "/" . $code;

        return $prefix;
    }

    public function getOvertimeDetail($where = null)
    {
        $this->builder->select($this->table . '.*,
        trx_overtime_detail.trx_overtime_detail_id,
        trx_overtime_detail.md_employee_id,
        md_employee.fullname as employee_name,
        md_employee.nik,
        trx_overtime_detail.startdate as startdate_line,
        trx_overtime_detail.enddate as enddate_line,
        trx_overtime_detail.enddate_realization,
        trx_overtime_detail.description,
        trx_overtime_detail.overtime_balance,
        trx_overtime_detail.overtime_expense,
        trx_overtime_detail.total,
        trx_overtime_detail.status,
        md_branch.name as branch_name,
        md_division.name as division_name');

        $this->builder->join('trx_overtime_detail', 'trx_overtime_detail.trx_overtime_id = ' . $this->table . '.trx_overtime_id', 'left');
        $this->builder->join('md_branch', 'md_branch.md_branch_id = ' . $this->table . '.md_branch_id', 'left');
        $this->builder->join('md_division', 'md_division.md_division_id = ' . $this->table . '.md_division_id', 'left');
        $this->builder->join('md_employee', 'md_employee.md_employee_id = trx_overtime_detail.md_employee_id', 'left');

        if ($where)
            $this->builder->where($where);

        $this->builder->orderBy('trx_overtime.md_division_id', 'ASC');
        $this->builder->orderBy('md_employee.fullname', 'ASC');
        $this->builder->orderBy('trx_overtime_detail.startdate', 'ASC');

        return $this->builder->get();
    }
}
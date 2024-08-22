<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_LeaveBalance extends Model
{
    protected $table                = 'trx_leavebalance';
    protected $primaryKey           = 'trx_leavebalance_id';
    protected $allowedFields        = [
        'md_employee_id',
        'year',
        'annual_allocation',
        'balance_amount',
        'carried_over_amount',
        'carry_over_expiry_date',
        'submissiondate',
        'startdate',
        'description',
        'created_by',
        'updated_by',
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\LeaveBalance';
    protected $allowCallbacks       = true;
    protected $beforeInsert         = [];
    protected $afterInsert          = [];
    protected $beforeUpdate         = [];
    protected $afterUpdate          = [];
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

    public function getBalance($where)
    {
        $this->builder->selectSum($this->table . '.balance_amount');
        $this->builder->where($where);
        return $this->builder->get()->getRow();
    }

    public function getSelect()
    {
        $sql = $this->table . '.*,
                md_employee.value as employee,
                md_employee.fullname as employee_fullname,
                md_branch.name as branch,
                md_division.name as divisi';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_employee', 'md_employee.md_employee_id = ' . $this->table . '.md_employee_id', 'left'),
            $this->setDataJoin('md_employee_branch', 'md_employee_branch.md_branch_id <> 0 AND md_employee_branch.md_employee_id = ' . $this->table . '.md_employee_id', 'left'),
            $this->setDataJoin('md_branch', 'md_branch.md_branch_id = md_employee_branch.md_branch_id', 'left'),
            $this->setDataJoin('md_employee_division', 'md_employee_division.md_employee_id = ' . $this->table . '.md_employee_id', 'left'),
            $this->setDataJoin('md_division', 'md_division.md_division_id = md_employee_division.md_division_id', 'left'),
            // $this->setDataJoin('trx_absent', 'trx_absent.trx_absent_id = ' . $this->table . '.record_id', 'left')
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
}

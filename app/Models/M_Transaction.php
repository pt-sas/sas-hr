<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Transaction extends Model
{
    protected $table                = 'md_transaction';
    protected $primaryKey           = 'md_transaction_id';
    protected $allowedFields        = [
        'transactiondate',
        'transactiontype',
        'year',
        'record_id',
        'table',
        'amount',
        'md_employee_id',
        'isprocessed',
        'description',
        'created_by',
        'updated_by',
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\Transaction';
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
        $sql = 'v_summary_leavebalance.*,
                md_employee.value as employee,
                md_employee.fullname as employee_fullname,
                md_branch.name as branch,
                md_division.name as divisi';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_employee', 'md_employee.md_employee_id = v_summary_leavebalance.md_employee_id', 'left'),
            $this->setDataJoin('md_employee_branch', 'md_employee_branch.md_branch_id <> 0 AND md_employee_branch.md_employee_id = v_summary_leavebalance.md_employee_id', 'left'),
            $this->setDataJoin('md_branch', 'md_branch.md_branch_id = md_employee_branch.md_branch_id', 'left'),
            $this->setDataJoin('md_employee_division', 'md_employee_division.md_employee_id = v_summary_leavebalance.md_employee_id', 'left'),
            $this->setDataJoin('md_division', 'md_division.md_division_id = md_employee_division.md_division_id', 'left')
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

    public function getBalance($where)
    {
        $this->builder->selectSum($this->table . '.amount');
        $this->builder->where($where);
        return $this->builder->get()->getRow();
    }
}

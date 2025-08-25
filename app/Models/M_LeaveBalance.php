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

    public function getSumBalanceAmount($id, $year)
    {
        $sql = "SELECT 
                tl.*,
                tl.balance_amount - coalesce(xt.totalline, 0) as balance,
                tl.carried_over_amount - coalesce(xt.totalline, 0) as balance_carried
                from trx_leavebalance tl 
                LEFT JOIN (select
                            ta.md_employee_id,
                            date_format(tad.date, '%Y') as year,
                            count(tad.trx_absent_detail_id) AS totalline
                        FROM trx_absent_detail tad
                            JOIN trx_absent ta ON tad.trx_absent_id = ta.trx_absent_id
                        WHERE ta.docstatus = 'IP'
                        and tad.isagree IN ('H','S') 
                        and ta.submissiontype = 100003
                        GROUP BY ta.md_employee_id, year) xt ON xt.md_employee_id = tl.md_employee_id and xt.year = tl.`year`
                WHERE 1=1
                AND tl.md_employee_id = ?
                AND tl.year = ?";

        return $this->db->query($sql, [$id, $year])->getRow();
    }

    public function getTotalBalance($id, $year)
    {
        $sql = "SELECT tl.*,
                tl.balance_amount - xt.reserved AS balance,
                tl.carried_over_amount - xt.reserved AS balance_carried
                FROM trx_leavebalance tl 
                LEFT JOIN (SELECT SUM(t.reserved_amount) AS reserved,
                t.year,
                t.md_employee_id
                FROM md_transaction t
                GROUP BY t.year, t.md_employee_id) xt ON xt.md_employee_id = tl.md_employee_id AND xt.year = tl.`year`
                WHERE 1=1
                AND tl.md_employee_id = ?
                AND tl.year = ?";

        return $this->db->query($sql, [$id, $year])->getRow();
    }
}
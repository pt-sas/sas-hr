<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_AllowanceAtt extends Model
{
    protected $table                = 'trx_allow_attendance';
    protected $primaryKey           = 'trx_allow_attendance_id';
    protected $allowedFields        = [
        'record_id',
        'table',
        'submissiondate',
        'submissiontype',
        'md_employee_id',
        'amount',
        'description',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\AllowanceAtt';
    protected $order                = ['submissiondate' => 'ASC'];
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

    public function getAllowance($where, $order = null)
    {
        $this->builder->selectSum($this->table . '.amount');
        $this->builder->select($this->table . '.submissiondate,' .
            $this->table . '.md_employee_id,' .
            $this->table . '.submissiontype');

        $this->builder->join('trx_absent', 'trx_absent.trx_absent_id = ' . $this->table . '.record_id AND table = "trx_absent"', 'left');

        if ($where)
            $this->builder->where($where);

        $this->builder->groupBy([
            $this->table . '.submissiondate',
            $this->table . '.md_employee_id',
            $this->table . '.submissiontype'
        ]);

        if ($order)
            $this->builder->orderBy($order);
        else
            $this->builder->orderBy($this->table . '.submissiondate', 'ASC');

        return $this->builder->get();
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

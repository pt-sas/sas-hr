<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_LeaveBalance extends Model
{
    protected $table                = 'trx_leavebalance';
    protected $primaryKey           = 'trx_leavebalance_id';
    protected $allowedFields        = [
        'record_id',
        'table',
        'md_employee_id',
        'amount',
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
        $this->builder->selectSum($this->table . '.amount');
        $this->builder->where($where);
        return $this->builder->get()->getRow();
    }
}

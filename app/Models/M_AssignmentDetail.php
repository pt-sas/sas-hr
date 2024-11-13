<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_AssignmentDetail extends Model
{
    protected $table                = 'trx_assignment_detail';
    protected $primaryKey           = 'trx_assignment_detail_id';
    protected $allowedFields        = [
        'trx_assignment_id',
        'md_employee_id',
        'nik',
        'description',
        'created_by',
        'updated_by',
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\AssignmentDetail';
    protected $allowCallbacks       = true;
    protected $beforeInsert         = [];
    protected $beforeUpdate         = [];
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
}
<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_OvertimeDetail extends Model
{
    protected $table                = 'trx_overtimedetail';
    protected $primaryKey           = 'trx_overtimedetail_id';
    protected $allowedFields        = [
        'trx_overtime_id',
        'md_employee_id',
        'nik',
        'startdate',
        'enddate',
        'description',
        'created_by',
        'updated_by',
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\OvertimeDetail';
	protected $request;
	protected $db;
	protected $builder;

    public function __construct(RequestInterface $request)
    {
        parent::__construct();
        $this->db = db_connect();
        $this->builder = $this->db->table($this->table);
        $this->request = $request;
        
    }
}
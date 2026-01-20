<?php

namespace App\Models;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\Model;

class M_PeriodControl extends Model
{
    protected $table            = 'md_period_control';
    protected $primaryKey       = 'md_period_control_id';
    protected $allowedFields    = [
        'md_period_id',
        'md_doctype_id',
        'period_status',
        'isactive',
        'created_by',
        'updated_by'
    ];

    protected $useTimestamps    = true;
    protected $returnType       = 'App\Entities\PeriodControl';

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

    public function countAll($field, $id)
    {
        $this->builder->where($field, $id);
        return $this->builder->countAllResults();
    }
}

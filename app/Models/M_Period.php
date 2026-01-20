<?php

namespace App\Models;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\Model;

class M_Period extends Model
{
    protected $table            = 'md_period';
    protected $primaryKey       = 'md_period_id';
    protected $allowedFields    = [
        'md_year_id',
        'periodno',
        'name',
        'startdate',
        'enddate',
        'description',
        'isactive',
        'created_by',
        'updated_by'
    ];

    protected $useTimestamps    = true;
    protected $returnType       = 'App\Entities\Period';

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

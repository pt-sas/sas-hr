<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_LeaveType extends Model
{
    protected $table            = 'md_leavetype';
    protected $primaryKey       = 'md_leavetype_id';
    protected $returnType       = 'App\Entities\LeaveType';
    protected $allowedFields    =
    [
        'value',
        'name',
        'gender',
        'duration',
        'duration_type',
        'description',
        'isactive',
        'created_by',
        'updated_by'
    ];

    protected $useTimestamps    = true;
    protected $column_order = [
        '', // Hide column
        '', // Number column
        'md_leavetype.value',
        'md_leavetype.name',
        'md_leavetype.gender',
        'md_leavetype.duration',
        'md_leavetype.duration_type',
        'md_leavetype.description',
        'md_leavetype.isactive'

    ];
    protected $column_search = [
        'md_leavetype.value',
        'md_leavetype.name',
        'md_leavetype.gender',
        'md_leavetype.duration',
        'md_leavetype.duration_type',
        'md_leavetype.description',
        'md_leavetype.isactive'
    ];
    protected $order = ['value' => 'ASC'];
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

<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_MassLeave extends Model
{
    protected $table            = 'md_massleave';
    protected $primaryKey       = 'md_massleave_id';
    protected $returnType       = 'App\Entities\MassLeave';
    protected $allowedFields    =
    [
        'name',
        'description',
        'isactive',
        'created_by',
        'updated_by',
        'startdate',
        'isaffect'
    ];

    protected $useTimestamps    = true;
    protected $column_order = [
        '', // Hide column
        '', // Number column
        'md_massleave.name',
        'startdate',
        'md_massleave.description',
        'md_massleave.isaffect',
        'md_massleave.isactive'

    ];
    protected $column_search = [
        'md_massleave.name',
        'startdate',
        'md_massleave.description',
        'md_massleave.isaffect',
        'md_massleave.isactive'
    ];
    protected $order = ['startdate' => 'ASC'];
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

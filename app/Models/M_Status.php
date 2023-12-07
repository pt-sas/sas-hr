<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Status extends Model
{
    protected $table            = 'md_status';
    protected $primaryKey       = 'md_status_id';
    protected $returnType       = 'App\Entities\Status';
    protected $allowedFields    =
    [
        'value',
        'name',
        'description',
        'isactive',
        'created_by',
        'updated_by'
    ];

    protected $useTimestamps    = true;
    protected $column_order = [
        '', // Hide column
        '', // Number column
        'md_status.value',
        'md_status.name',
        'md_status.description',
        'md_status.isactive'
    ];
    protected $column_search = [
        'md_status.value',
        'md_status.name',
        'md_status.description',
        'md_status.isactive'
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

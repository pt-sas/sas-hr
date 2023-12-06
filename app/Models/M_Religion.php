<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Religion extends Model
{
    protected $table            = 'md_religion';
    protected $primaryKey       = 'md_religion_id';
    protected $returnType       = 'App\Entities\Religion';
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
        'md_religion.value',
        'md_religion.name',
        'md_religion.description',
        'md_religion.isactive'
    ];
    protected $column_search = [
        'md_religion.value',
        'md_religion.name',
        'md_religion.description',
        'md_religion.isactive'
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

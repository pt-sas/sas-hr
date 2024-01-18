<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Levelling extends Model
{
    protected $table            = 'md_levelling';
    protected $primaryKey       = 'md_levelling_id';
    protected $returnType       = 'App\Entities\Levelling';
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
        'md_levelling.value',
        'md_levelling.name',
        'md_levelling.description',
        'md_levelling.isactive'
    ];
    protected $column_search = [
        'md_levelling.value',
        'md_levelling.name',
        'md_levelling.description',
        'md_levelling.isactive'
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
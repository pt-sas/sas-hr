<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Leveling extends Model
{
    protected $table            = 'md_leveling';
    protected $primaryKey       = 'md_leveling_id';
    protected $returnType       = 'App\Entities\Leveling';
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
        'md_leveling.value',
        'md_leveling.name',
        'md_leveling.description',
        'md_leveling.isactive'
    ];
    protected $column_search = [
        'md_leveling.value',
        'md_leveling.name',
        'md_leveling.description',
        'md_leveling.isactive'
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

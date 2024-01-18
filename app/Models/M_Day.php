<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Day extends Model
{
    protected $table            = 'md_day';
    protected $primaryKey       = 'md_day_id';
    protected $returnType       = 'App\Entities\Day';
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
        'md_day.value',
        'md_day.name',
        'md_day.description',
        'md_day.isactive'
    ];
    protected $column_search = [
        'md_day.value',
        'md_day.name',
        'md_day.description',
        'md_day.isactive'
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

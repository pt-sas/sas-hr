<?php

namespace App\Models;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\Model;

class M_Year extends Model
{
    protected $table            = 'md_year';
    protected $primaryKey       = 'md_year_id';
    protected $allowedFields    = [
        'year',
        'description',
        'isactive',
        'created_by',
        'updated_by'
    ];

    protected $useTimestamps    = true;
    protected $returnType       = 'App\Entities\Year';
    protected $column_order = [
        '', // Hide column
        '', // Number column
        'md_year.year',
        'md_year.description',
        'md_year.isactive'
    ];
    protected $column_search = [
        'md_year.year',
        'md_year.description',
        'md_year.isactive'
    ];
    protected $order = ['year' => 'DESC'];

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

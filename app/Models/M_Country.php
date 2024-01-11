<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Country extends Model
{
    protected $table            = 'md_country';
    protected $primaryKey       = 'md_country_id';
    protected $returnType       = 'App\Entities\Country';
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
        'md_country.value',
        'md_country.name',
        'md_country.description',
        'md_country.isactive'
    ];
    protected $column_search = [
        'md_country.value',
        'md_country.name',
        'md_country.description',
        'md_country.isactive'
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

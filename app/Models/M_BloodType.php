<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_BloodType extends Model
{
    protected $table            = 'md_bloodtype';
    protected $primaryKey       = 'md_bloodtype_id';
    protected $returnType       = 'App\Entities\BloodType';
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
        'md_bloodtype.value',
        'md_bloodtype.name',
        'md_bloodtype.description',
        'md_bloodtype.isactive'
    ];
    protected $column_search = [
        'md_bloodtype.value',
        'md_bloodtype.name',
        'md_bloodtype.description',
        'md_bloodtype.isactive'
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

<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Configuration extends Model
{
    protected $table                = 'sys_configuration';
    protected $primaryKey           = 'sys_configuration_id';
    protected $allowedFields        = [
        'value',
        'name',
        'description',
        'isactive',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\Configuration';
    protected $column_order         = [
        '', // Hide column
        '', // Number column
        'sys_configuration.name',
        'sys_configuration.value',
        'sys_configuration.description',
        'sys_configuration.isactive'
    ];
    protected $column_search        = [
        'sys_configuration.name',
        'sys_configuration.value',
        'sys_configuration.description',
        'sys_configuration.isactive'
    ];
    protected $order                = ['name' => 'ASC'];
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

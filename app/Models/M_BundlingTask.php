<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_BundlingTask extends Model
{
    protected $table                = 'trx_bundling_task';
    protected $primaryKey           = 'trx_bundling_task_id';
    protected $allowedFields        = [
        'trx_bundling_id',
        'iscompleted',
        'name',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\BundlingTask';
    protected $afterInsert          = [];
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

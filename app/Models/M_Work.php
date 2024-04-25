<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Work extends Model
{
    protected $table                = 'md_work';
    protected $primaryKey           = 'md_work_id';
    protected $allowedFields        = [
        'name',
        'workhour',
        'fulltime',
        'isactive',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\Work';
    protected $column_order         = [
        '', // Hide column
        '', // Number column
        'md_branch.name',
        'md_branch.workhoue',
        'md_branch.isactive'
    ];
    protected $column_search        = [
        'md_branch.name',
        'md_branch.workhoue',
        'md_branch.isactive'
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

<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_DocumentType extends Model
{
    protected $table                = 'md_doctype';
    protected $primaryKey           = 'md_doctype_id';
    protected $allowedFields        = [
        'name',
        'description',
        'isactive',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\DocumentType';
    protected $column_order         = [
        '', // Hide column
        '', // Number column
        'md_branch.name',
        'md_branch.description',
        'md_branch.isactive'
    ];
    protected $column_search        = [
        'md_branch.name',
        'md_branch.description',
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

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
        'updated_by',
        'isrealization',
        'isapprovedline',
        'is_realization_mgr',
        'days_realization_mgr',
        'is_realization_hrd',
        'days_realization_hrd',
        'auto_not_approve_days'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\DocumentType';
    protected $column_order         = [
        '', // Hide column
        '', // Number column
        'md_doctype.name',
        'md_doctype.description',
        'md_doctype.isactive'
    ];
    protected $column_search        = [
        'md_doctype.name',
        'md_doctype.description',
        'md_doctype.isactive'
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

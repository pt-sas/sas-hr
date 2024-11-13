<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_AssignmentDate extends Model
{
    protected $table                = 'trx_assignment_date';
    protected $primaryKey           = 'trx_assignment_date_id';
    protected $allowedFields        = [
        'trx_assignment_detail_id',
        'date',
        'isagree',
        'table',
        'reference_id',
        'comment',
        'description',
        'created_by',
        'updated_by',
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\AssignmentDate';
    protected $allowCallbacks       = true;
    protected $beforeInsert         = [];
    protected $beforeUpdate         = [];
    protected $beforeDelete         = [];
    protected $afterDelete          = [];
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

    public function getDetail($field, $where)
    {
        $this->builder->select($this->table . '.*,
            trx_assignment.trx_assignment_id,
            trx_assignment.documentno');

        $this->builder->join('trx_assignment_detail', 'trx_assignment_detail.trx_assignment_detail_id = ' . $this->table . '.trx_assignment_detail_id', 'left');
        $this->builder->join('trx_assignment', 'trx_assignment.trx_assignment_id = trx_assignment_detail.trx_assignment_id', 'left');

        if (!empty($where)) {
            $this->builder->where($field, $where);
        }

        return $this->builder->get();
    }
}

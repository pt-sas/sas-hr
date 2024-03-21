<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_AbsentDetail extends Model
{
    protected $table                = 'trx_absent_detail';
    protected $primaryKey           = 'trx_absent_detail_id';
    protected $allowedFields        = [
        'trx_absent_id',
        'lineno',
        'date',
        'isagree',
        'ref_absent_detail_id',
        'created_by',
        'updated_by',
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\AbsentDetail';
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
            trx_absent.trx_absent_id,
            trx_absent.documentno');

        $this->builder->join('trx_absent', 'trx_absent.trx_absent_id = ' . $this->table . '.trx_absent_id', 'left');

        if (!empty($where)) {
            $this->builder->where($field, $where);
        }

        return $this->builder->get();
    }

    public function getAbsentDetail($where)
    {
        $this->builder->select($this->table . '.*,
            trx_absent.trx_absent_id,
            trx_absent.nik,
            trx_absent.documentno');

        $this->builder->join('trx_absent', 'trx_absent.trx_absent_id = ' . $this->table . '.trx_absent_id', 'left');
        $this->builder->where($where);
        return $this->builder->get();
    }
}

<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Attend extends Model
{
    protected $table                = 'trx_attend';
    protected $primaryKey           = 'trx_attend_id';
    protected $allowedFields        = [
        'nik',
        'checktime',
        'status',
        'verify',
        'reserved',
        'reserved2',
        'serialnumber',
        'created_by',
        'updated_by',
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\Attend';
    protected $order                = ['date' => 'ASC'];
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

    public function getAttendance($where)
    {
        $builder = $this->db->table("v_attendance");

        $builder->select('*');

        if ($where)
            $builder->where($where);

        return $builder->get();
    }

    private function setDataJoin($tableJoin, $columnJoin, $typeJoin = "inner")
    {
        return [
            "tableJoin" => $tableJoin,
            "columnJoin" => $columnJoin,
            "typeJoin" => $typeJoin
        ];
    }
}

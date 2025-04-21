<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_ProxySpecialDetail extends Model
{
    protected $table                = 'trx_proxy_special_detail';
    protected $primaryKey           = 'trx_proxy_special_detail_id';
    protected $allowedFields        = [
        'trx_proxy_special_id',
        'lineno',
        'sys_role_id',
        'created_by',
        'updated_by',
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\ProxySpecialDetail';
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

    /**
     * Change value of field data
     *
     * @param $data Data
     * @return array
     */
    public function doChangeValueField($data, $id, $dataHeader): array
    {
        $result = [];

        $number = 1;

        foreach ($data as $row) :
            if (property_exists($row, "lineno"))
                $row->lineno = $number;

            $result[] = $row;
            $number++;
        endforeach;

        log_message('debug', json_encode($result));

        return $result;
    }
}
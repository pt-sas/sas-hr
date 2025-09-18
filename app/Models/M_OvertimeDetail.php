<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_OvertimeDetail extends Model
{
    protected $table                = 'trx_overtime_detail';
    protected $primaryKey           = 'trx_overtime_detail_id';
    protected $allowedFields        = [
        'trx_overtime_id',
        'md_employee_id',
        'startdate',
        'enddate',
        'description',
        'overtime_balance',
        'overtime_expense',
        'enddate_realization',
        'total',
        'isagree',
        'created_by',
        'updated_by',
        'realization_by',
        'approve_date',
        'realization_date_superior',
        'realization_by_superior',
        'realization_date_hrd',
        'realization_by_hrd'
    ];
    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\OvertimeDetail';
    protected $request;
    protected $db;
    protected $builder;

    public function __construct(RequestInterface $request)
    {
        parent::__construct();
        $this->db = db_connect();
        $this->builder = $this->db->table($this->table);
        $this->request = $request;
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

        foreach ($data as $row) :
            if (isset($dataHeader->startdate)) {
                $row->startdate = date('Y-m-d', strtotime($dataHeader->startdate)) . " " . $row->starttime;
                $row->enddate = date('Y-m-d', strtotime($dataHeader->enddate)) . " " . $row->endtime;
            }
            $result[] = $row;
        endforeach;

        return $result;
    }

    public function getRealizationOvertime($where)
    {
        $builder = $this->db->table("v_realization_overtime");

        if ($where)
            $builder->where($where);

        return $builder->get();
    }
}
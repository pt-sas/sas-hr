<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_MasterBundling extends Model
{
    protected $table                = 'trx_master_bundling';
    protected $primaryKey           = 'trx_master_bundling_id';

    protected $allowedFields        = [
        'name',
        'bundling_type',
        'nomimal_type',
        'recurring_type',
        'md_division_id',
        'submissiondate',
        'minimal_time',
        'estimate_time',
        'recurring_type',
        'nominal_type',
        'startdate',
        'enddate',
        'description',
        'isapproved',
        'approved_by',
        'approveddate',
        'sys_wfscenario_id',
        'created_by',
        'updated_by',
    ];

    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\MasterBundling';

    protected $order                = [
        'submissiondate' => 'DESC',
        'name'           => 'DESC'
    ];

    protected $column_order         = [
        '', // Hide column
        '', // Number column
        'trx_master_bundling.name',
        'md_division.name',
        'trx_master_bundling.bundling_type',
        'trx_master_bundling.startdate',
        'trx_master_bundling.description',
        'sys_user.username'
    ];

    protected $column_search        = [
        'trx_master_bundling.name',
        'md_division.name',
        'trx_master_bundling.bundling_type',
        'trx_master_bundling.startdate',
        'trx_master_bundling.description',
        'sys_user.username'
    ];

    protected $request;
    protected $db;
    protected $builder;

    public function __construct(RequestInterface $request)
    {
        parent::__construct();

        $this->db      = db_connect();
        $this->request = $request;
        $this->builder = $this->db->table($this->table);
    }

    public function getSelect()
    {
        $sql = $this->table . '.*,
                sys_user.name as createdby,
                md_division.name as division';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_division', 'md_division.md_division_id = ' . $this->table . '.md_division_id', 'left'),
            $this->setDataJoin('sys_user', 'sys_user.sys_user_id = ' . $this->table . '.created_by', 'left')
        ];

        return $sql;
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

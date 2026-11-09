<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_MasterBundling extends Model
{
    protected $table                = 'trx_master_bundling';
    protected $primaryKey           = 'trx_master_bundling_id';

    protected $allowedFields        = [
        'value',
        'name',
        'md_doctype_id',
        'minimum_hour',
        'compensation_type',
        'period_type',
        'project_value',
        'divisionrule',
        'isprorate',
        'submissiontype',
        'description',
        'docstatus',
        'isapproved',
        'approveddate',
        'sys_wfscenario_id',
        'created_by',
        'updated_by',
    ];

    protected $useTimestamps        = true;
    protected $returnType           = 'App\Entities\MasterBundling';

    protected $order                = [
        'name'           => 'DESC'
    ];

    protected $column_order         = [
        '', // Hide column
        '', // Number column
        'trx_master_bundling.name',
        'trx_master_bundling.created_at',
        'trx_master_bundling.description',
        'sys_user.username'
    ];

    protected $column_search        = [
        'trx_master_bundling.name',
        'trx_master_bundling.created_at',
        'trx_master_bundling.description',
        'sys_user.username'
    ];

    protected $request;
    protected $db;
    protected $builder;

    protected $Pengajuan_Master_Paket = 100032;

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
                md_doctype.name as doctype_name,
                sys_user.name as createdby';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_doctype', 'md_doctype.md_doctype_id = ' . $this->table . '.md_doctype_id', 'left'),
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

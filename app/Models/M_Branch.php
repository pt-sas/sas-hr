<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Branch extends Model
{
    protected $table                = 'md_branch';
    protected $primaryKey           = 'md_branch_id';
    protected $allowedFields        = [
        'value',
        'name',
        'description',
        'address',
        'phone',
        'leader_id',
        'isactive',
        'created_by',
        'updated_by'
    ];
    protected $useTimestamps        = true;
    protected $returnType = 'App\Entities\Branch';
    protected $column_order = [
        '', // Hide column
        '', // Number column
        'md_branch.value',
        'md_branch.name',
        'md_branch.address',
        'md_employee.name',
        'md_branch.phone',
        'md_branch.isactive'
    ];
    protected $column_search = [
        'md_branch.value',
        'md_branch.name',
        'md_branch.address',
        'md_employee.name',
        'md_branch.phone',
        'md_branch.isactive'
    ];
    protected $order = ['value' => 'ASC'];
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

    public function getSelect()
    {
        $sql = $this->table . '.*,
                md_employee.name as leader';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_employee', 'md_employee.md_employee_id = ' . $this->table . '.leader_id', 'left')
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

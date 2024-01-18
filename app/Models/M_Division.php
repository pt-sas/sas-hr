<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Division extends Model
{
    protected $table      = 'md_division';
    protected $primaryKey = 'md_division_id';
    protected $allowedFields = [
        'value',
        'name',
        'description',
        'isactive',
        'created_by',
        'updated_by',
        'md_branch_id'
    ];
    protected $useTimestamps = true;
    protected $returnType = 'App\Entities\Division';
    protected $column_order = [
        '', // Hide column
        '', // Number column
        'md_division.value',
        'md_division.name',
        'md_division.description',
        'md_branch.name',
        'md_division.isactive'
    ];
    protected $column_search = [
        'md_division.value',
        'md_division.name',
        'md_division.description',
        'md_branch.name',
        'md_division.isactive'
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
        md_branch.name as branch';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_branch', 'md_branch.md_branch_id =' . $this->table . '.md_branch_id', 'left')
        ];

        return $sql;
    }

    public function setDataJoin($tableJoin, $columnJoin, $typeJoin = "inner")
    {
        return [
            "tableJoin" => $tableJoin,
            "columnJoin" => $columnJoin,
            "typeJoin" => $typeJoin
        ];
    }
}

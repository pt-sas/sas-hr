<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Position extends Model
{
    protected $table            = 'md_position';
    protected $primaryKey       = 'md_position_id';
    protected $returnType       = 'App\Entities\Position';
    protected $allowedFields    =
    [
        'value',
        'name',
        'description',
        'isactive',
        'ismandatoryduta',
        'created_by',
        'updated_by',
        'md_division_id'
    ];

    protected $useTimestamps    = true;
    protected $column_order = [
        '', // Hide column
        '', // Number column
        'md_position.value',
        'md_position.name',
        'md_position.description',
        'md_division.name',
        'md_position.isactive'

    ];
    protected $column_search = [
        'md_position.value',
        'md_position.name',
        'md_position.description',
        'md_division.name',
        'md_position.isactive'
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
        md_division.name as division';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_division', 'md_division.md_division_id =' . $this->table . '.md_division_id', 'left')
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

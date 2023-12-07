<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_City extends Model
{
    protected $table            = 'md_city';
    protected $primaryKey       = 'md_city_id';
    protected $returnType       = 'App\Entities\City';
    protected $allowedFields    =
    [
        'value',
        'name',
        'description',
        'isactive',
        'created_by',
        'updated_by',
        'md_province_id'
    ];

    protected $useTimestamps    = true;
    protected $column_order = [
        '', // Hide column
        '', // Number column
        'md_city.value',
        'md_city.name',
        'md_province.name',
        'md_city.description',
        'md_city.isactive'
    ];
    protected $column_search = [
        'md_city.value',
        'md_city.name',
        'md_province.name',
        'md_city.description',
        'md_city.isactive'
    ];
    protected $order = ['name' => 'ASC'];
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
            md_province.name as province';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_province', 'md_province.md_province_id = ' . $this->table . '.md_province_id', 'left')
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

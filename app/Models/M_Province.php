<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Province extends Model
{
    protected $table            = 'md_province';
    protected $primaryKey       = 'md_province_id';
    protected $returnType       = 'App\Entities\Province';
    protected $allowedFields    =
    [
        'value',
        'name',
        'description',
        'isactive',
        'created_by',
        'updated_by',
        'md_country_id'
    ];

    protected $useTimestamps    = true;
    protected $column_order = [
        '', // Hide column
        '', // Number column
        'md_province.value',
        'md_province.name',
        'md_province.description',
        'md_country.name',
        'md_province.isactive'
    ];
    protected $column_search = [
        'md_province.value',
        'md_province.name',
        'md_province.description',
        'md_country.name',
        'md_province.isactive'
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
            md_country.name as country';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_country', 'md_country.md_country_id = ' . $this->table . '.md_country_id', 'left')
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

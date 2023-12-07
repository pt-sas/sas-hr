<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_District extends Model
{
    protected $table            = 'md_district';
    protected $primaryKey       = 'md_district_id';

    protected $returnType       = 'App\Entities\District';
    protected $allowedFields    = [
        'name',
        'value',
        'description',
        'created_by',
        'updated_by',
        'isactive',
        'md_city_id'
    ];

    protected $useTimestamps    = true;
    protected $column_order = [
        '',
        '',
        'md_district.value',
        'md_district.name',
        'md_city.name',
        'md_district.description',
        'md_district.isactive'
    ];
    protected $column_search = [
        '',
        '',
        'md_district.value',
        'md_district.name',
        'md_city.name',
        'md_district.description',
        'md_district.isactive'
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
        md_city.name as city';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_city', 'md_city.md_city_id =' . $this->table . '.md_city_id', 'left')
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

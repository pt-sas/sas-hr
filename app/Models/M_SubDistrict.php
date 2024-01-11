<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_SubDistrict extends Model
{
    protected $table            = 'md_subdistrict';
    protected $primaryKey       = 'md_subdistrict_id';

    protected $returnType       = 'App\Entities\SubDistrict';
    protected $allowedFields    = [
        'name',
        'value',
        'description',
        'created_by',
        'updated_by',
        'isactive',
        'md_district_id'
    ];

    protected $useTimestamps    = true;
    protected $column_order = [
        '',
        '',
        'md_subdistrict.value',
        'md_subdistrict.name',
        'md_subdistrict.description',
        'md_district.name',
        'md_subdistrict.isactive'
    ];
    protected $column_search = [
        'md_subdistrict.value',
        'md_subdistrict.name',
        'md_subdistrict.description',
        'md_district.name',
        'md_subdistrict.isactive'
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
        md_district.name as district';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_district', 'md_district.md_district_id =' . $this->table . '.md_district_id', 'left')
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

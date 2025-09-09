<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Holiday extends Model
{
    protected $table            = 'md_holiday';
    protected $primaryKey       = 'md_holiday_id';
    protected $returnType       = 'App\Entities\Holiday';
    protected $allowedFields    =
    [
        'name',
        'description',
        'isactive',
        'created_by',
        'updated_by',
        'startdate',
        'md_religion_id'
    ];

    protected $useTimestamps    = true;
    protected $column_order = [
        '', // Hide column
        '', // Number column
        'md_holiday.name',
        'startdate',
        'md_holiday.description',
        'md_religion.name',
        'md_holiday.isactive'

    ];
    protected $column_search = [
        'md_holiday.name',
        'startdate',
        'md_holiday.description',
        'md_religion.name',
        'md_holiday.isactive'
    ];
    protected $order = ['startdate' => 'ASC'];
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
        md_religion.name as religion';

        return $sql;
    }

    public function getJoin()
    {
        $sql = [
            $this->setDataJoin('md_religion', 'md_religion.md_religion_id =' . $this->table . '.md_religion_id', 'left')
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

    public function getHolidayDate()
    {
        $mMassLeave = new M_MassLeave($this->request);

        $date1 = $this->where([
            // "DATE_FORMAT(startdate, '%Y')"  => date("Y"),
            "isactive"                      => "Y"
        ])->findAll();

        foreach ($date1 as $row) :
            $holiday[] = $row->getStartDate();
        endforeach;

        $date2 = $mMassLeave->where([
            // "DATE_FORMAT(startdate, '%Y')"  => date("Y"),
            "isaffect"                      => "Y",
            "isactive"                      => "Y"
        ])->findAll();

        foreach ($date2 as $row) :
            $massLeave[] = $row->getStartDate();
        endforeach;

        $arr = array_unique(array_merge($holiday, $massLeave));
        sort($arr);

        return $arr;
    }
}
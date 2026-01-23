<?php

namespace App\Models;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\Model;

class M_Year extends Model
{
    protected $table            = 'md_year';
    protected $primaryKey       = 'md_year_id';
    protected $allowedFields    = [
        'year',
        'description',
        'isactive',
        'created_by',
        'updated_by'
    ];

    protected $useTimestamps    = true;
    protected $returnType       = 'App\Entities\Year';
    protected $column_order = [
        '', // Hide column
        '', // Number column
        'md_year.year',
        'md_year.description',
        'md_year.isactive'
    ];
    protected $column_search = [
        'md_year.year',
        'md_year.description',
        'md_year.isactive'
    ];
    protected $order = ['year' => 'DESC'];

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

    public function getPeriodStatus($date, $doctype)
    {
        $this->builder->select('*');
        $this->builder->join('md_period', "{$this->table}.md_year_id = md_period.md_year_id");
        $this->builder->join('md_period_control', 'md_period.md_period_id = md_period_control.md_period_id');
        $this->builder->where("DATE(md_period.startdate) <= '{$date}' AND DATE(md_period.enddate) >= '{$date}'");
        $this->builder->where("md_period_control.md_doctype_id", $doctype);
        $this->builder->where('md_year.isactive', 'Y');

        return $this->builder->get();
    }
}

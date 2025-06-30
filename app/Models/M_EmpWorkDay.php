<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_EmpWorkDay extends Model
{
	protected $table                = 'md_employee_work';
	protected $primaryKey           = 'md_employee_work_id';
	protected $allowedFields        = [
		'md_employee_id',
		'md_work_id',
		'validfrom',
		'validto',
		'description',
		'isactive',
		'created_by',
		'updated_by',
	];
	protected $useTimestamps        = true;
	protected $returnType 			= 'App\Entities\EmpWorkDay';
	protected $allowCallbacks		= true;
	protected $beforeInsert			= [];
	protected $afterInsert			= [];
	protected $beforeUpdate			= [];
	protected $afterUpdate			= [];
	protected $beforeDelete			= [];
	protected $afterDelete			= [];
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

	public function getEmpWorkDetail($where)
	{
		$this->builder->select('md_work_detail.*,
			md_day.name as day,
            md_work.name as work');

		$this->builder->join('md_work', 'md_work.md_work_id = ' . $this->table . '.md_work_id', 'inner');
		$this->builder->join('md_work_detail', 'md_work_detail.md_work_id = md_work.md_work_id', 'inner');
		$this->builder->join('md_day', 'md_day.md_day_id = md_work_detail.md_day_id', 'left');
		$this->builder->where($where);
		return $this->builder->get();
	}
}

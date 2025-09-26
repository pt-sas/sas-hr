<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_WorkDetail extends Model
{
	protected $table                = 'md_work_detail';
	protected $primaryKey           = 'md_work_detail_id';
	protected $allowedFields        = [
		'md_work_id',
		'md_day_id',
		'startwork',
		'breakstart',
		'breakend',
		'endwork',
		'description',
		'isactive',
		'created_by',
		'updated_by',
	];
	protected $useTimestamps        = true;
	protected $returnType 			= 'App\Entities\WorkDetail';
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

	public function getWorkDetail($where)
	{
		$this->builder->select($this->table . '.*,
			md_day.name as day,
            md_work.name as work,
			md_employee_work.md_employee_id');

		$this->builder->join('md_work', 'md_work.md_work_id = ' . $this->table . '.md_work_id', 'inner');
		$this->builder->join('md_day', 'md_day.md_day_id = ' . $this->table . '.md_day_id', 'left');
		$this->builder->join('md_employee_work', 'md_employee_work.md_work_id = md_work.md_work_id', 'left');
		$this->builder->where($where);
		return $this->builder->get();
	}
}

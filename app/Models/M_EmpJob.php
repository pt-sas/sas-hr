<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_EmpJob extends Model
{
	protected $table                = 'md_employee_job';
	protected $primaryKey           = 'md_employee_job_id';
	protected $allowedFields        = [
		'md_employee_id',
		'company',
		'startdate',
		'enddate',
		'position',
		'salary',
		'reason',
		'isactive',
		'created_by',
		'updated_by',
	];
	protected $useTimestamps        = true;
	protected $returnType 			= 'App\Entities\EmpJob';
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

	public function getDataReport($where = null)
	{
		$builder = $this->db->table('v_rpt_employee_job');

		if ($where)
			$builder->where($where);

		return $builder->get();
	}
}
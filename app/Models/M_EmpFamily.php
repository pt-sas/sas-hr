<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_EmpFamily extends Model
{
	protected $table                = 'md_employee_family';
	protected $primaryKey           = 'md_employee_family_id';
	protected $allowedFields        = [
		'md_employee_id',
		'member',
		'name',
		'gender',
		'age',
		'education',
		'job',
		'status',
		'dateofdeath',
		'isactive',
		'created_by',
		'updated_by',
	];
	protected $useTimestamps        = true;
	protected $returnType 			= 'App\Entities\EmpFamily';
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
}

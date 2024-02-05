<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_RuleValue extends Model
{
	protected $table                = 'md_rule_value';
	protected $primaryKey           = 'md_rule_value_id';
	protected $allowedFields        = [
		'md_rule_detail_id',
		'name',
		'value',
		'isactive',
		'created_by',
		'updated_by'
	];
	protected $useTimestamps        = true;
	protected $returnType 			= 'App\Entities\RuleValue';
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

	public function countAll($field, $id)
	{
		$this->builder->where($field, $id);
		return $this->builder->countAllResults();
	}
}

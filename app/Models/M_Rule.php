<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Rule extends Model
{
	protected $table                = 'md_rule';
	protected $primaryKey           = 'md_rule_id';
	protected $allowedFields        = [
		'name',
		'condition',
		'value',
		'menu_url',
		'priority',
		'isdetail',
		'isactive',
		'created_by',
		'updated_by'
	];
	protected $useTimestamps        = true;
	protected $returnType 			= 'App\Entities\Rule';
	protected $allowCallbacks		= true;
	protected $beforeInsert			= [];
	protected $afterInsert			= [];
	protected $beforeUpdate			= [];
	protected $afterUpdate			= [];
	protected $beforeDelete			= [];
	protected $afterDelete			= [];
	protected $column_order 		= [
		'', // Hide column
		'', // Number column
		'md_rule.name',
		'md_rule.condition',
		'md_rule.value',
		'md_rule.menu_id',
		'md_rule.priority',
		'md_rule.isdetail',
		'md_rule.isactive'
	];
	protected $column_search 		= [
		'md_rule.name',
		'md_rule.condition',
		'md_rule.value',
		'md_rule.menu_id',
		'md_rule.priority',
		'md_rule.isdetail',
		'md_rule.isactive'
	];
	protected $order 				= ['name' => 'ASC'];
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

	private function setDataJoin($tableJoin, $columnJoin, $typeJoin = "inner")
	{
		return [
			"tableJoin" => $tableJoin,
			"columnJoin" => $columnJoin,
			"typeJoin" => $typeJoin
		];
	}
}

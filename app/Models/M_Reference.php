<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Reference extends Model
{
	protected $table                = 'sys_reference';
	protected $primaryKey           = 'sys_reference_id';
	protected $allowedFields        = [
		'name',
		'description',
		'validationtype',
		'isactive',
		'created_by',
		'updated_by',
	];
	protected $useTimestamps        = true;
	protected $returnType 			= 'App\Entities\Reference';
	protected $allowCallbacks		= true;
	protected $beforeInsert			= [];
	protected $afterInsert			= [];
	protected $beforeUpdate			= [];
	protected $afterUpdate			= [];
	protected $beforeDelete			= [];
	protected $afterDelete			= ['deleteDetail'];
	protected $column_order = [
		'', // Hide column
		'', // Number column
		'sys_reference.name',
		'sys_reference.description',
		'sys_reference.validationtype',
		'sys_reference.isactive'
	];
	protected $column_search = [
		'sys_reference.name',
		'sys_reference.description',
		'sys_reference.validationtype',
		'sys_reference.isactive'
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
		$sql = $this->table . '.*,' .
			'sys_ref_detail.name as ref_detail';

		return $sql;
	}

	public function getJoin()
	{
		//* SYS_Reference Validation Types 
		$defaultID = 1;

		$sql = [
			$this->setDataJoin('sys_ref_detail', 'sys_ref_detail.sys_reference_id = ' . $defaultID . ' AND sys_ref_detail.value = ' . $this->table . '.validationtype', 'left'),
		];

		return $sql;
	}

	private function setDataJoin($tableJoin, $columnJoin, $typeJoin = "inner")
	{
		return [
			"tableJoin" => $tableJoin,
			"columnJoin" => $columnJoin,
			"typeJoin" => $typeJoin
		];
	}

	public function deleteDetail(array $rows)
	{
		$refDetail = new M_ReferenceDetail($this->request);
		$refDetail->where($this->primaryKey, $rows['id'])->delete();
	}

	public function findBy($where = null, $field = null, $orderBy = [])
	{
		//* Check arg where if not null value
		if (!empty($where))
			$this->builder->where($where);

		if (!is_array($field) && !is_array($where) && !empty($field) && !empty($where))
			$this->builder->where($field, $where);

		$this->builder->join('sys_ref_detail', 'sys_ref_detail.sys_reference_id = ' . $this->table . '.' . $this->primaryKey);

		if (is_array($orderBy) && !empty($orderBy))
			$this->builder->orderBy($orderBy['field'], $orderBy['option']);

		return $this->builder->get();
	}
}

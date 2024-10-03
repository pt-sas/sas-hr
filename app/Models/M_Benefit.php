<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_Benefit extends Model
{
	protected $table                = 'md_benefit';
	protected $primaryKey           = 'md_benefit_id';
	protected $allowedFields        = [
		'name',
		'md_branch_id',
		'md_division_id',
		'md_position_id',
		'md_levelling_id',
		'md_status_id',
		'isactive',
		'created_by',
		'updated_by'
	];
	protected $useTimestamps        = true;
	protected $returnType 			= 'App\Entities\Benefit';
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
		'md_benefit.name',
		'md_branch.name',
		'md_division.name',
		'md_levelling.name',
		'md_position.name',
		'md_status.name',
		'md_benefit.isactive'
	];
	protected $column_search 		= [
		'md_benefit.name',
		'md_branch.name',
		'md_division.name',
		'md_levelling.name',
		'md_position.name',
		'md_status.name',
		'md_benefit.isactive'
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

	public function getSelect()
	{
		$sql = $this->table . '.*,
				md_branch.name as cabang,
				md_division.name as divisi,
				md_levelling.name as level,
				md_position.name as jabatan,
				md_status.name as status';

		return $sql;
	}

	public function getJoin()
	{
		$sql = [
			$this->setDataJoin('md_branch', 'md_branch.md_branch_id = ' . $this->table . '.md_branch_id', 'left'),
			$this->setDataJoin('md_division', 'md_division.md_division_id = ' . $this->table . '.md_division_id', 'left'),
			$this->setDataJoin('md_levelling', 'md_levelling.md_levelling_id = ' . $this->table . '.md_levelling_id', 'left'),
			$this->setDataJoin('md_position', 'md_position.md_position_id = ' . $this->table . '.md_position_id', 'left'),
			$this->setDataJoin('md_status', 'md_status.md_status_id = ' . $this->table . '.md_status_id', 'left'),
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
}

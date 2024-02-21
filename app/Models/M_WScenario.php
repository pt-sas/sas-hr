<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_WScenario extends Model
{
	protected $table                = 'sys_wfscenario';
	protected $primaryKey           = 'sys_wfscenario_id';
	protected $allowedFields        = [
		'name',
		'lineno',
		'grandtotal',
		'menu',
		'md_status_id',
		'md_branch_id',
		'md_division_id',
		'scenariotype',
		'description',
		'isactive',
		'created_by',
		'updated_by',
		'md_levelling_id',
	];
	protected $useTimestamps		= true;
	protected $returnType			= 'App\Entities\WScenario';
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
		'sys_wfscenario.name',
		'sys_wfscenario.lineno',
		'sys_wfscenario.grandtotal',
		'sys_wfscenario.menu',
		'md_status.name',
		'md_branch.name',
		'md_division.name',
		'sys_wfscenario.description',
		'sys_wfscenario.isactive'
	];
	protected $column_search = [
		'sys_wfscenario.name',
		'sys_wfscenario.lineno',
		'sys_wfscenario.grandtotal',
		'sys_wfscenario.menu',
		'md_status.name',
		'md_branch.name',
		'md_division.name',
		'sys_wfscenario.description',
		'sys_wfscenario.isactive'
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
		$sql = $this->table . '.*,' .
			'md_status.name as status,
			md_branch.name as branch,
			md_division.name as division,
			md_levelling.name as level';
		return $sql;
	}

	public function getJoin()
	{
		$sql = [
			$this->setDataJoin('md_status', 'md_status.md_status_id = ' . $this->table . '.md_status_id', 'left'),
			$this->setDataJoin('md_branch', 'md_branch.md_branch_id = ' . $this->table . '.md_branch_id', 'left'),
			$this->setDataJoin('md_division', 'md_division.md_division_id = ' . $this->table . '.md_division_id', 'left'),
			$this->setDataJoin('md_levelling', 'md_levelling.md_levelling_id = ' . $this->table . '.md_levelling_id', 'left'),
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

	public function getScenario(string $menu, int $md_groupasset_id = null, int $md_status_id = null, int $md_branch_id = null, int $md_division_id = null, string $scenariotype = null)
	{
		$this->builder->select('sys_wfscenario_id');
		$this->builder->where([
			'menu' 		=> $menu,
			'isactive'	=> 'Y'
		]);

		if (!is_null($md_groupasset_id)) {
			$this->builder->where('md_groupasset_id', $md_groupasset_id);
		} else {
			$this->builder->where('(md_groupasset_id IS NULL OR md_groupasset_id = 0)');
		}

		if (!is_null($md_status_id)) {
			$this->builder->where('md_status_id', $md_status_id);
		} else {
			$this->builder->where('(md_status_id IS NULL OR md_status_id = 0)');
		}

		if (!is_null($md_branch_id)) {
			$this->builder->where('md_branch_id', $md_branch_id);
		} else {
			$this->builder->where('(md_branch_id IS NULL OR md_branch_id = 0)');
		}

		if (!is_null($md_division_id)) {
			$this->builder->where('md_division_id', $md_division_id);
		} else {
			$this->builder->where('(md_division_id IS NULL OR md_division_id = 0)');
		}

		if (!is_null($scenariotype)) {
			$this->builder->where('scenariotype', $scenariotype);
		} else {
			$this->builder->where('(scenariotype IS NULL OR scenariotype = 0)');
		}

		// if (!empty($grandtotal)) {
		// 	$this->builder->where('grandtotal >=', $grandtotal);
		// }

		$this->builder->orderBy('lineno', 'DESC');

		$sql = $this->builder->get()->getRow();
		return !is_null($sql) ? $sql->sys_wfscenario_id : null;
	}
}

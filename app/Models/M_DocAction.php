<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_DocAction extends Model
{
	protected $table                = 'sys_docaction';
	protected $primaryKey           = 'sys_docaction_id';
	protected $allowedFields        = [
		'sys_role_id',
		'menu',
		'ref_list',
		'isactive',
		'created_by',
		'updated_by'
	];
	protected $useTimestamps        = true;
	protected $returnType           = 'App\Entities\DocAction';
	protected $column_order = [
		'', // Hide column
		'', // Number column
		'sys_role_id',
		'menu',
		'ref_list',
		'isactive',
	];
	protected $column_search = [
		'sys_role_id',
		'menu',
		'ref_list',
		'isactive',
	];
	protected $order = ['sys_role_id' => 'ASC'];
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
			'sys_role.name as role,
			sys_ref_detail.name as doc_action';

		return $sql;
	}

	public function getJoin()
	{
		//* _DocAction List
		$defaultID = 12;

		$sql = [
			$this->setDataJoin('sys_role', 'sys_role.sys_role_id = ' . $this->table . '.sys_role_id', 'left'),
			$this->setDataJoin('sys_ref_detail', 'sys_ref_detail.sys_reference_id = ' . $defaultID . ' AND sys_ref_detail.value = ' . $this->table . '.docaction', 'left'),
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

	public function checkExistRoles($where = null, $like = [])
	{
		$sql = "SELECT r.*
		FROM sys_role r
		WHERE r.isactive = 'Y' ";

		// if (!empty($where)) {
		// 	$sql .= "AND NOT EXISTS(SELECT 1 FROM sys_docaction doc
		// 						WHERE doc.sys_role_id = r.sys_role_id)";
		// } else {
		$sql .= "AND NOT EXISTS(SELECT 1 FROM sys_docaction sd WHERE sd.sys_role_id = r.sys_role_id)";
		// }

		if (count($like) > 0) {
			foreach ($like as $key => $row) :
				$sql .= "AND $key LIKE '%" . $row . "%'";
			endforeach;
		}

		$sql .= "ORDER BY r.name ASC";

		return $this->db->query($sql, $where);
	}
}

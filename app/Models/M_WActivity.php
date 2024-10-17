<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_WActivity extends Model
{
	protected $table                = 'sys_wfactivity';
	protected $primaryKey           = 'sys_wfactivity_id';
	protected $allowedFields        = [
		'sys_wfscenario_id',
		'sys_wfresponsible_id',
		'sys_user_id',
		'state',
		'processed',
		'textmsg',
		'table',
		'record_id',
		'menu',
		'isactive',
		'created_by',
		'updated_by',
		'tableline',
		'recordline_id'
	];
	protected $useTimestamps        = true;
	protected $returnType           = 'App\Entities\WActivity';
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

	private function getRole()
	{
		$sql = "SELECT sys_user_role.sys_role_id 
				FROM sys_user_role
				WHERE sys_user_role.sys_user_id = ?";

		$query = $this->db->query($sql, [session()->get('sys_user_id')]);

		$role = [];

		if ($query->getNumRows() > 0) {
			foreach ($query->getResult() as $row) :
				$role[] = $row->sys_role_id;
			endforeach;
		}

		return $role;
	}

	public function getActivity(string $type = null, string $where = null)
	{
		$role = $this->getRole();

		$this->builder->select($this->table . '.*,
				sys_wfscenario.name as scenario,
				sys_wfresponsible.name as wfresponsible');
		$this->builder->join('sys_wfscenario', 'sys_wfscenario.sys_wfscenario_id = ' . $this->table . '.sys_wfscenario_id', 'left');
		$this->builder->join('sys_wfresponsible', 'sys_wfresponsible.sys_wfresponsible_id = ' . $this->table . '.sys_wfresponsible_id', 'left');
		$this->builder->where([
			$this->table . '.state'			=> 'OS',
			$this->table . '.processed'		=> 'N'
		]);

		// Saat user mempunyai lebih dari 1 role approval, dokumen yg harus diapprove belum muncul
		if (!empty($role))
			$this->builder->whereIn('sys_wfresponsible.sys_role_id', $role);

		if ($where)
			$this->builder->where($where);

		$this->builder->orderBy($this->table . '.created_at', 'ASC');

		if (!is_null($type) && strtolower($type) === "count")
			$sql = $this->builder->countAllResults();
		else
			$sql = $this->builder->get()->getResult();

		return $sql;
	}

	public function getDataTrx(string $table, $id, string $tableLine = null)
	{
		$_table = is_null($tableLine) ? $table : $tableLine;

		$fields = $this->db->getFieldData($_table);

		$this->builder = $this->db->table($_table);

		$query = $_table . '.*,
						sys_user.name as usercreated_by,
						usp.name as userupdated_by';

		$this->builder->join('sys_user', 'sys_user.sys_user_id = ' . $_table . '.created_by', 'left');
		$this->builder->join('sys_user usp', 'usp.sys_user_id = ' . $_table . '.updated_by', 'left');

		if (is_null($tableLine)) {
			$this->builder->whereIn($_table . ".docstatus", ["IP", "RE"]);
		} else {
			$fieldH = $this->db->getFieldData($table);

			foreach ($fieldH as $field) {
				if ($field->primary_key == 1) {
					$this->builder->join($table, $table . '.' . $field->name . ' = ' . $_table . '.' . $field->name);
					$this->builder->join('md_employee', 'md_employee.md_employee_id = ' . $table . '.md_employee_id', 'left');
				}
			}

			$query .= ',';
			$query .= $table . '.documentno,';
			$query .= 'md_employee.fullname as employee,
						md_employee.value,';

			$this->builder->where($tableLine . ".isagree", "H");
		}

		$this->builder->select($query);

		foreach ($fields as $field) {
			if ($field->primary_key == 1) {
				$this->builder->where([
					$_table . "." . $field->name => $id
				]);
			}
		}

		$sql = $this->builder->get()->getRow();

		return $sql;
	}
}

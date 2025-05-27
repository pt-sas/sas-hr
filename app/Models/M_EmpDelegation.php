<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_EmpDelegation extends Model
{
	protected $table      		= 'sys_emp_delegation';
	protected $primaryKey 		= 'sys_emp_delegation_id';
	protected $allowedFields 	= [
		'sys_user_id',
		'md_employee_id',
		'isactive',
		'updated_by',
		'created_by'
	];
	protected $useTimestamps 	= true;
	protected $returnType 		= 'App\Entities\EmpDelegation';
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

	public function create($post)
	{
		$changeLog = new M_ChangeLog($this->request);
		$mUser = new M_User($this->request);
		$mEmployee = new M_Employee($this->request);

		$sys_user_id = $post['sys_user_id'];
		$md_employee_id = array_filter($post['md_employee_id']);
		$user = $mUser->where('sys_user_id', $sys_user_id)->first();

		$list = $this->where("sys_user_id", $sys_user_id)->findAll();
		$arr = [];

		if ($list) {
			foreach ($list as $row) :
				$employee = $mEmployee->where('md_employee_id', $row->md_employee_id)->first();

				// Delete data when update
				if (!in_array($row->md_employee_id, $md_employee_id)) {
					$this->delete($row->sys_emp_delegation_id);

					$changeLog->insertLog($this->table, 'md_employee_id', $row->sys_emp_delegation_id, $employee->value, null, 'D', $user->name);
				} else {
					$this->entity = new \App\Entities\EmpDelegation();
					$this->entity->setEmpDelegationId($row->sys_emp_delegation_id);
					$this->entity->setUpdatedBy(session()->get('sys_user_id'));
					$this->save($this->entity);
				}

				// Get list data in this before update
				$arr[] = $row->md_employee_id;
			endforeach;
		}

		if (!empty($md_employee_id)) {
			// Add new data
			foreach ($md_employee_id as $value) :
				if (!in_array($value, $arr)) {
					$result = null;

					$this->entity = new \App\Entities\EmpDelegation();
					$this->entity->md_employee_id = $value;
					$this->entity->created_at = date('Y-m-d H:i:s');
					$this->entity->created_by = session()->get('sys_user_id');
					$this->entity->updated_at = date('Y-m-d H:i:s');
					$this->entity->updated_by = session()->get('sys_user_id');
					$this->entity->sys_user_id = $sys_user_id;
					$this->entity->isactive = setCheckbox(isset($post['isactive']));

					$result = $this->save($this->entity);

					if ($result) {
						$employee = $mEmployee->where('md_employee_id', $value)->first();
						$changeLog->insertLog($this->table, 'md_employee_id', $this->getInsertID(), null, $employee->value, 'I', $user->name);
					}
				}
			endforeach;
		}
	}

	public function createFromEmployee($post)
	{
		$changeLog = new M_ChangeLog($this->request);
		$mUser = new M_User($this->request);
		$mEmployee = new M_Employee($this->request);

		$user = $mUser->where('md_employee_id', $post['md_ambassador_id'])->first();
		$md_employee_id = $post['md_employee_id'];

		$list = $this->where('md_employee_id', $md_employee_id)->first();

		$employee = $mEmployee->where('md_employee_id', $md_employee_id)->first();
		if ($user) {
			// Delete data when update
			if ($list && $list->sys_user_id != $user->sys_user_id) {
				$this->delete($list->sys_emp_delegation_id);
				$oldUser = $mUser->where('sys_user_id', $list->sys_user_id)->first();
				$changeLog->insertLog($this->table, 'md_employee_id', $list->sys_emp_delegation_id, $employee->value, null, 'D', $oldUser->name);

				$result = null;
				$this->entity = new \App\Entities\EmpDelegation();
				$this->entity->md_employee_id = $md_employee_id;
				$this->entity->created_at = date('Y-m-d H:i:s');
				$this->entity->created_by = session()->get('sys_user_id');
				$this->entity->updated_at = date('Y-m-d H:i:s');
				$this->entity->updated_by = session()->get('sys_user_id');
				$this->entity->sys_user_id = $user->sys_user_id;

				$result = $this->save($this->entity);

				if ($result) {
					$changeLog->insertLog($this->table, 'md_employee_id', $this->getInsertID(), null, $employee->value, 'I', $user->name);
				}
			} else {
				$result = null;
				$this->entity = new \App\Entities\EmpDelegation();
				$this->entity->md_employee_id = $md_employee_id;
				$this->entity->created_at = date('Y-m-d H:i:s');
				$this->entity->created_by = session()->get('sys_user_id');
				$this->entity->updated_at = date('Y-m-d H:i:s');
				$this->entity->updated_by = session()->get('sys_user_id');
				$this->entity->sys_user_id = $user->sys_user_id;

				$result = $this->save($this->entity);

				if ($result) {
					$changeLog->insertLog($this->table, 'md_employee_id', $this->getInsertID(), null, $employee->value, 'I', $user->name);
				}
			}
		} else if (!$user && $list) {
			$this->delete($list->sys_emp_delegation_id);
			$oldUser = $mUser->where('sys_user_id', $list->sys_user_id)->first();
			$changeLog->insertLog($this->table, 'md_employee_id', $list->sys_emp_delegation_id, $employee->value, null, 'D', $oldUser->name);
		}
	}
}

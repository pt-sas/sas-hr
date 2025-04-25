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
		'isactive'
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
		$sys_user_id = $post['sys_user_id'];
		$md_employee_id = $post['md_employee_id'];
		$data = [];

		$data['isactive'] = setCheckbox(isset($post['isactive']));
		$data['sys_user_id'] = $sys_user_id;
		$result = false;

		// Insert data
		if (!isset($post['id'])) {
			foreach ($md_employee_id as $value) :
				if (!empty($value)) {
					$data['md_employee_id'] = $value;
					$data['created_at'] = date('Y-m-d H:i:s');
					$data['created_by'] = session()->get('sys_user_id');
					$data['updated_at'] = date('Y-m-d H:i:s');
					$data['updated_by'] = session()->get('sys_user_id');

					$result = $this->builder->insert($data);
				}
			endforeach;
		} else {
			$list = $this->where("sys_user_id", $sys_user_id)->findAll();
			$arr = [];

			foreach ($list as $row) :
				// Delete data when update
				if (!in_array($row->md_employee_id, $md_employee_id)) {
					$result = $this->builder->where($this->primaryKey, $row->{$this->primaryKey})->delete();
				} else {
					$data['updated_at'] = date('Y-m-d H:i:s');
					$data['updated_by'] = session()->get('sys_user_id');
					$result = $this->builder->where($this->primaryKey, $row->{$this->primaryKey})->update($data);
				}

				// Get list data in this before update
				$arr[] = $row->md_employee_id;
			endforeach;

			// Add new data when update
			foreach ($md_employee_id as $value) :
				if (!empty($value)) {
					if (!in_array($value, $arr)) {
						$data['md_employee_id'] = $value;
						$data['created_at'] = date('Y-m-d H:i:s');
						$data['created_by'] = session()->get('sys_user_id');
						$data['updated_at'] = date('Y-m-d H:i:s');
						$data['updated_by'] = session()->get('sys_user_id');

						$result = $this->builder->insert($data);
					}
				}
			endforeach;
		}

		return $result;
	}
}

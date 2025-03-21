<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_EmpBranch extends Model
{
	protected $table      		= 'md_employee_branch';
	protected $primaryKey 		= 'md_employee_branch_id';
	protected $allowedFields 	= [
		'md_employee_id',
		'md_branch_id',
		'description',
		'isactive'
	];
	protected $useTimestamps 	= true;
	protected $returnType 		= 'App\Entities\EmpBranch';
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
		$md_employee_id = $post['md_employee_id'];
		$md_branch_id = $post['md_branch_id'];
		$data = [];

		$data['isactive'] = setCheckbox(isset($post['isactive']));
		$data['md_employee_id'] = $md_employee_id;

		// Insert data
		if (!isset($post['id'])) {
			foreach ($md_branch_id as $value) :
				$data['md_branch_id'] = $value;
				$data['created_at'] = date('Y-m-d H:i:s');
				$data['created_by'] = session()->get('sys_user_id');
				$data['updated_at'] = date('Y-m-d H:i:s');
				$data['updated_by'] = session()->get('sys_user_id');

				$result = $this->builder->insert($data);
			endforeach;
		} else {
			$list = $this->where("md_employee_id", $md_employee_id)->findAll();
			$arr = [];

			foreach ($list as $row) :
				// Delete data when update
				if (!in_array($row->md_branch_id, $md_branch_id)) {
					$result = $this->builder->where($this->primaryKey, $row->{$this->primaryKey})->update($data);
				} else {
					$data['updated_at'] = date('Y-m-d H:i:s');
					$data['updated_by'] = session()->get('sys_user_id');
					$result = $this->builder->where($this->primaryKey, $row->{$this->primaryKey})->update($data);
				}

				// Get list data in this before update
				$arr[] = $row->md_branch_id;
			endforeach;

			// Add new data when update
			foreach ($md_branch_id as $value) :
				if (!in_array($value, $arr)) {
					$data['md_branch_id'] = $value;
					$data['created_at'] = date('Y-m-d H:i:s');
					$data['created_by'] = session()->get('sys_user_id');
					$data['updated_at'] = date('Y-m-d H:i:s');
					$data['updated_by'] = session()->get('sys_user_id');

					$result = $this->builder->insert($data);
				}
			endforeach;
		}

		return $result;
	}

	public function getBranchDetail($where)
	{
		$this->builder->select($this->table . '.*, md_branch.name as branch_name');
		$this->builder->join('md_branch', 'md_employee_branch.md_branch_id = md_branch.md_branch_id', 'left');
		if ($where)
			$this->builder->where($where);

		return $this->builder->get();
	}
}

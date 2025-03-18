<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_EmpDivision extends Model
{
	protected $table      		= 'md_employee_division';
	protected $primaryKey 		= 'md_employee_division_id';
	protected $allowedFields 	= [
		'md_employee_id',
		'md_division_id',
		'description',
		'isactive'
	];
	protected $useTimestamps 	= true;
	protected $returnType 		= 'App\Entities\EmpDivision';
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
		$md_division_id = $post['md_division_id'];
		$data = [];

		$data['isactive'] = setCheckbox(isset($post['isactive']));
		$data['md_employee_id'] = $md_employee_id;

		// Insert data
		if (!isset($post['id'])) {
			foreach ($md_division_id as $value) :
				$data['md_division_id'] = $value;
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
				if (!in_array($row->md_division_id, $md_division_id)) {
					$result = $this->builder->where($this->primaryKey, $row->{$this->primaryKey})->delete();
				} else {
					$data['updated_at'] = date('Y-m-d H:i:s');
					$data['updated_by'] = session()->get('sys_user_id');
					$result = $this->builder->where($this->primaryKey, $row->{$this->primaryKey})->update($data);
				}

				// Get list data in this before update
				$arr[] = $row->md_division_id;
			endforeach;

			// Add new data when update
			foreach ($md_division_id as $value) :
				if (!in_array($value, $arr)) {
					$data['md_division_id'] = $value;
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

	public function getDivisionDetail($where)
	{
		$this->builder->select($this->table . '.*, md_division.name as division_name');
		$this->builder->join('md_division', 'md_employee_division.md_division_id = md_division.md_division_id', 'left');
		if ($where)
			$this->builder->where($where);

		return $this->builder->get();
	}
}

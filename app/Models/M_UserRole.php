<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Models\M_User;
use CodeIgniter\HTTP\RequestInterface;

class M_UserRole extends Model
{
	protected $table      = 'sys_user_role';
	protected $primaryKey = 'sys_user_role_id';
	protected $allowedFields = [
		'sys_role_id',
		'sys_user_id',
		'updated_by',
		'created_by',
		'isactive'
	];
	protected $useTimestamps = true;
	protected $returnType = 'App\Entities\UserRole';
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
		$sys_role_id = $post['sys_role_id'];
		$data = [];

		$data['isactive'] = setCheckbox(isset($post['isactive']));
		$data['sys_user_id'] = $sys_user_id;

		// Insert data
		if (!isset($post['id'])) {
			foreach ($sys_role_id as $value) :
				$data['sys_role_id'] = $value;
				$data['created_at'] = date('Y-m-d H:i:s');
				$data['created_by'] = session()->get('sys_user_id');
				$data['updated_at'] = date('Y-m-d H:i:s');
				$data['updated_by'] = session()->get('sys_user_id');

				$result = $this->builder->insert($data);
			endforeach;
		} else {
			$list = $this->where("sys_user_id", $sys_user_id)->findAll();
			$arr = [];

			foreach ($list as $row) :
				// Delete data when update
				if (!in_array($row->sys_role_id, $sys_role_id)) {
					$result = $this->builder->where($this->primaryKey, $row->{$this->primaryKey})->delete();
				} else {
					$data['updated_at'] = date('Y-m-d H:i:s');
					$data['updated_by'] = session()->get('sys_user_id');
					$result = $this->builder->where($this->primaryKey, $row->{$this->primaryKey})->update($data);
				}

				// Get list data in this before update
				$arr[] = $row->sys_role_id;
			endforeach;

			// Add new data when update
			foreach ($sys_role_id as $value) :
				if (!in_array($value, $arr)) {
					$data['sys_role_id'] = $value;
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
}
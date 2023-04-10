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
		$user = new M_User($this->request);

		$sys_user_id = $post['sys_user_id'];
		$role = $post['role'];
		$data = [];

		$data['isactive'] = setCheckbox(isset($post['isactive']));
		$data['sys_user_id'] = $sys_user_id;

		// Insert data
		if (!isset($post['id'])) {
			foreach ($post['role'] as $value) :
				$data['sys_role_id'] = $value;
				$data['created_at'] = date('Y-m-d H:i:s');
				$data['created_by'] = session()->get('sys_user_id');
				$data['updated_at'] = date('Y-m-d H:i:s');
				$data['updated_by'] = session()->get('sys_user_id');

				$result = $this->builder->insert($data);
			endforeach;
		} else {
			$list = $user->detail(['sur.sys_user_id' => $sys_user_id])->getResult();
			$arrRole = [];

			foreach ($list as $row) :
				// Delete role when update user
				if (!in_array($row->role, $role)) {
					$result = $this->builder->where('sys_user_role_id', $row->sys_user_role_id)->delete();
				} else {
					$data['updated_at'] = date('Y-m-d H:i:s');
					$data['updated_by'] = session()->get('sys_user_id');
					$result = $this->builder->where('sys_user_role_id', $row->sys_user_role_id)->update($data);
				}

				// Get list role in this user before update
				$arrRole[] = $row->role;
			endforeach;

			// Add new role when update user
			foreach ($role as $value) :
				if (!in_array($value, $arrRole)) {
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

<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;
use App\Models\M_UserRole;

class M_User extends Model
{
	protected $table                = 'sys_user';
	protected $primaryKey           = 'sys_user_id';
	protected $allowedFields        = [
		'username',
		'name',
		'description',
		'password',
		'email',
		'isactive',
		'datelastlogin',
		'datepasswordchange',
		'updated_at',
		'created_by',
		'updated_by'
	];
	protected $useTimestamps        = true;
	protected $returnType           = 'App\Entities\User';
	protected $allowCallbacks		= true;
	protected $beforeInsert			= [];
	protected $afterInsert			= ['createUserRole'];
	protected $beforeUpdate			= [];
	protected $afterUpdate			= ['createUserRole'];
	protected $beforeDelete			= [];
	protected $afterDelete			= ['deleteUserRole'];
	protected $column_order = [
		'', // Hide column
		'', // Number column
		'username',
		'name',
		'description',
		'email',
		'isactive',
	];
	protected $column_search = [
		'username',
		'name',
		'description',
		'email',
		'isactive',
	];
	protected $order = ['name' => 'ASC'];
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

	public function detail($arrParam = [], $field = null, $where = null)
	{
		$this->builder->select($this->table . '.*,' .
			'sur.sys_role_id as role,
			sur.sys_user_role_id');

		$this->builder->join('sys_user_role sur', 'sur.sys_user_id = ' . $this->table . '.sys_user_id', 'left');
		$this->builder->join('sys_role sr', 'sur.sys_role_id = sr.sys_role_id', 'left');

		if (count($arrParam) > 0) {
			$this->builder->where($arrParam);
		} else {
			if (!empty($where)) {
				$this->builder->where($field, $where);
			}
		}

		$this->builder->orderBy('sr.name', 'ASC');

		$query = $this->builder->get();
		return $query;
	}

	public function createUserRole(array $rows)
	{
		$post = $this->request->getVar();

		if (isset($post['role'])) {
			$userRole = new M_UserRole($this->request);

			$post['role'] = explode(',', $post['role']);
			$post['sys_user_id'] = $rows['id'];

			$userRole->create($post);
		}
	}

	public function deleteUserRole(array $rows)
	{
		$userRole = new M_UserRole($this->request);

		$userRole->where($this->primaryKey, $rows['id'])->delete();
	}
}

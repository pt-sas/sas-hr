<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\HTTP\RequestInterface;

class M_ProxySwitching extends Model
{
	protected $table                = 'trx_proxy_switching';
	protected $primaryKey           = 'trx_proxy_switching_id';
	protected $allowedFields        = [
		'proxytype',
		'trx_proxy_special_detail_id',
		'sys_role_id',
		'sys_user_from',
		'sys_user_to',
		'startdate',
		'enddate',
		'state',
		'isactive',
		'updated_at',
		'created_by',
		'updated_by',
	];
	protected $useTimestamps        = true;
	protected $returnType           = 'App\Entities\ProxySwitching';
	protected $beforeInsert			= [];
	protected $afterInsert			= [];
	protected $beforeUpdate			= [];
	protected $afterUpdate			= [];
	protected $beforeDelete			= [];
	protected $afterDelete			= [];
	protected $order 				= ['startdate' => 'ASC'];
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

	public function getProxyDetail($where)
	{
		$this->builder->select("{$this->table}.*,
		ps.documentno,
		ps.startdate as proxy_startdate,
		ps.enddate as proxy_enddate");

		$this->builder->join('trx_proxy_special_detail psd', "psd.trx_proxy_special_detail_id = {$this->table}.trx_proxy_special_detail_id", 'left');
		$this->builder->join('trx_proxy_special ps', "ps.trx_proxy_special_id = psd.trx_proxy_special_id", 'left');

		$this->builder->where($where);

		return $this->builder->get();
	}

	public function insertProxy($userFrom, $userTo, $role, $isSpecial = false, $ref_id = null, $isPermanent = null)
	{
		$mUserRole = new M_UserRole($this->request);
		$mChangeLog = new M_ChangeLog($this->request);

		$proxyType = $isSpecial ? 'special' : 'reguler';

		$user_by = 100000;
		$result = false;

		//TODO : Checking is Role already exists in user switching
		$isRoleExists = $mUserRole->where(['sys_user_id' => $userTo, 'sys_role_id' => $role])->first();

		if (!$isRoleExists) {
			//TODO : Inserting role into user switching
			$entity = new \App\Entities\UserRole();

			$entity->setRoleId($role);
			$entity->setUserId($userTo);
			$entity->setUpdatedBy($user_by);
			$entity->setCreatedBy($user_by);

			//TODO : if inserting success then inserting to table Proxy Switching
			if ($mUserRole->save($entity)) {
				$entity = new \App\Entities\ProxySwitching();

				$entity->setProxyType($proxyType);
				$entity->setRoleId($role);
				$entity->setUserFrom($userFrom);
				$entity->setUserTo($userTo);
				$entity->setStartDate(date('Y-m-d H:i:s'));

				if ($isPermanent === "Y") {
					$entity->setState('CO');
				} else {
					$entity->setState('IP');
				}

				$entity->setUpdatedBy($user_by);
				$entity->setCreatedBy($user_by);

				if ($isSpecial === true) {
					$entity->setProxySpecialDetailId($ref_id);
				}

				$this->save($entity);
				$result = true;
			}
		}

		// TODO : if isPermanent is Y then delete Proxy from old user
		if ($isPermanent === "Y") {
			$oldUserRole = $mUserRole->where(['sys_user_id' => $userFrom, 'sys_role_id' => $role])->first();

			if ($oldUserRole) {
				$mUserRole->delete($oldUserRole->sys_user_role_id);
				$mChangeLog->insertLog($mUserRole->table, $mUserRole->primaryKey, $oldUserRole->sys_user_role_id, $oldUserRole->sys_user_role_id, null, "D", "Delete Old User Proxy");
			}
		}

		return $result;
	}
}
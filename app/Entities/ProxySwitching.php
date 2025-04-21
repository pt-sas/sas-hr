<?php

namespace App\Entities;

use CodeIgniter\Entity;

class ProxySwitching extends Entity
{
	protected $trx_proxy_switching_id;
	protected $proxytype;
	protected $trx_proxy_special_detail_id;
	protected $sys_role_id;
	protected $sys_user_from;
	protected $sys_user_to;
	protected $startdate;
	protected $enddate;
	protected $state;
	protected $isactive;
	protected $created_by;
	protected $updated_by;

	protected $dates   = [
		'created_at',
		'updated_at',
		'deleted_at',
	];

	public function getProxySwitchingId()
	{
		return $this->attributes['trx_proxy_switching_id'];
	}

	public function setProxySwitchingId($trx_proxy_switching_id)
	{
		$this->attributes['trx_proxy_switching_id'] = $trx_proxy_switching_id;
	}

	public function getProxySpecialDetailId()
	{
		return $this->attributes['trx_proxy_special_detail_id'];
	}

	public function setProxySpecialDetailId($trx_proxy_special_detail_id)
	{
		$this->attributes['trx_proxy_special_detail_id'] = $trx_proxy_special_detail_id;
	}

	public function getProxyType()
	{
		return $this->attributes['proxytype'];
	}

	public function setProxyType($proxytype)
	{
		$this->attributes['proxytype'] = $proxytype;
	}

	public function getRoleId()
	{
		return $this->attributes['sys_role_id'];
	}

	public function setRoleId($sys_role_id)
	{
		$this->attributes['sys_role_id'] = $sys_role_id;
	}

	public function getUserFrom()
	{
		return $this->attributes['sys_user_from'];
	}

	public function setUserFrom($sys_user_from)
	{
		$this->attributes['sys_user_from'] = $sys_user_from;
	}

	public function getUserTo()
	{
		return $this->attributes['sys_user_to'];
	}

	public function setUserTo($sys_user_to)
	{
		$this->attributes['sys_user_to'] = $sys_user_to;
	}

	public function getStartDate()
	{
		// if (!empty($this->attributes['startdate']))
		// return format_dmy($this->attributes['startdate'], "-");

		return $this->attributes['startdate'];
	}

	public function setStartDate($startdate)
	{
		$this->attributes['startdate'] = $startdate;
	}

	public function getEndDate()
	{
		// if (!empty($this->attributes['enddate']))
		//     return format_dmy($this->attributes['enddate'], "-");

		return $this->attributes['enddate'];
	}

	public function setEndDate($enddate)
	{
		$this->attributes['enddate'] = $enddate;
	}

	public function getState()
	{
		return $this->attributes['state'];
	}

	public function setState($state)
	{
		$this->attributes['state'] = $state;
	}

	public function getIsActive()
	{
		return $this->attributes['isactive'];
	}

	public function setIsActive($isactive)
	{
		return $this->attributes['isactive'] = $isactive;
	}

	public function getCreatedAt()
	{
		return $this->attributes['created_at'];
	}

	public function getCreatedBy()
	{
		return $this->attributes['created_by'];
	}

	public function setCreatedBy($created_by)
	{
		$this->attributes['created_by'] = $created_by;
	}

	public function getUpdatedAt()
	{
		return $this->attributes['updated_at'];
	}

	public function getUpdatedBy()
	{
		return $this->attributes['updated_by'];
	}

	public function setUpdatedBy($updated_by)
	{
		$this->attributes['updated_by'] = $updated_by;
	}
}
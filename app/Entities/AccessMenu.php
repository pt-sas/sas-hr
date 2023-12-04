<?php

namespace App\Entities;

use CodeIgniter\Entity;

class AccessMenu extends Entity
{
	protected $sys_access_menu_id;
	protected $sys_role_id;
	protected $sys_menu_id;
	protected $sys_submenu_id;
	protected $isview;
	protected $iscreate;
	protected $isupdate;
	protected $isdelete;
	protected $isactive;
	protected $created_by;
	protected $updated_by;

	protected $dates   = [
		'created_at',
		'updated_at',
		'deleted_at',
	];

	public function getAccessMenuId()
	{
		return $this->attributes['sys_access_menu_id'];
	}

	public function setAccessMenuId($sys_access_menu_id)
	{
		$this->attributes['sys_access_menu_id'] = $sys_access_menu_id;
	}

	public function getRoleId()
	{
		return $this->attributes['sys_role_id'];
	}

	public function setRoleId($sys_role_id)
	{
		$this->attributes['sys_role_id'] = $sys_role_id;
	}

	public function getMenuId()
	{
		return $this->attributes['sys_menu_id'];
	}

	public function setMenuId($sys_menu_id)
	{
		$this->attributes['sys_menu_id'] = $sys_menu_id;
	}

	public function getSubmenuId()
	{
		return $this->attributes['sys_submenu_id'];
	}

	public function setSubmenuId($sys_submenu_id)
	{
		$this->attributes['sys_submenu_id'] = $sys_submenu_id;
	}

	public function getIsView()
	{
		return $this->attributes['isview'];
	}

	public function setIsView($isview)
	{
		return $this->attributes['isview'] = $isview;
	}

	public function getIsCreate()
	{
		return $this->attributes['iscreate'];
	}

	public function setIsCreate($iscreate)
	{
		return $this->attributes['iscreate'] = $iscreate;
	}

	public function getIsUpdate()
	{
		return $this->attributes['isupdate'];
	}

	public function setIsUpdate($isupdate)
	{
		return $this->attributes['isupdate'] = $isupdate;
	}

	public function getIsDelete()
	{
		return $this->attributes['isdelete'];
	}

	public function setIsDelete($isdelete)
	{
		return $this->attributes['isdelete'] = $isdelete;
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

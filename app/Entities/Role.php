<?php

namespace App\Entities;

use CodeIgniter\Entity;

class Role extends Entity
{
	protected $sys_role_id;
	protected $value;
	protected $name;
	protected $description;
	protected $isactive;
	protected $ismanual;
	protected $iscanexport;
	protected $iscanreport;
	protected $isallowmultipleprint;
	protected $created_by;
	protected $updated_by;

	protected $dates   = [
		'created_at',
		'updated_at',
		'deleted_at',
	];

	public function getRoleId()
	{
		return $this->attributes['sys_role_id'];
	}

	public function setRoleId($sys_role_id)
	{
		$this->attributes['sys_role_id'] = $sys_role_id;
	}

	public function getValue()
	{
		return $this->attributes['value'];
	}

	public function setValue($value)
	{
		$this->attributes['value'] = $value;
	}

	public function getName()
	{
		return $this->attributes['name'];
	}

	public function setName($name)
	{
		$this->attributes['name'] = $name;
	}

	public function getDescription()
	{
		return $this->attributes['description'];
	}

	public function setDescription($description)
	{
		$this->attributes['description'] = $description;
	}

	public function getIsActive()
	{
		return $this->attributes['isactive'];
	}

	public function setIsActive($isactive)
	{
		return $this->attributes['isactive'] = $isactive;
	}

	public function getIsManual()
	{
		return $this->attributes['ismanual'];
	}

	public function setIsManual($ismanual)
	{
		return $this->attributes['ismanual'] = $ismanual;
	}

	public function getIsCanExport()
	{
		return $this->attributes['iscanexport'];
	}

	public function setIsCanExport($iscanexport)
	{
		return $this->attributes['iscanexport'] = $iscanexport;
	}

	public function getIsCanReport()
	{
		return $this->attributes['iscanreport'];
	}

	public function setIsCanReport($iscanreport)
	{
		return $this->attributes['iscanreport'] = $iscanreport;
	}

	public function getIsAllowMultiplePrint()
	{
		return $this->attributes['isallowmultipleprint'];
	}

	public function setIsAllowMultiplePrint($isallowmultipleprint)
	{
		return $this->attributes['isallowmultipleprint'] = $isallowmultipleprint;
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

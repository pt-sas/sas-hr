<?php

namespace App\Entities;

use CodeIgniter\Entity;

class DivAccess extends Entity
{
	protected $sys_user_id;
	protected $md_division_id;
	protected $isactive;
	protected $created_by;
	protected $updated_by;

	protected $dates   = [
		'created_at',
		'updated_at',
		'deleted_at',
	];

	public function getUserId()
	{
		return $this->attributes['sys_user_id'];
	}

	public function setUserId($sys_user_id)
	{
		$this->attributes['sys_user_id'] = $sys_user_id;
	}

	public function getDivId()
	{
		return $this->attributes['md_division_id'];
	}

	public function setDivId($md_division_id)
	{
		$this->attributes['md_division_id'] = $md_division_id;
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

<?php

namespace App\Entities;

use CodeIgniter\Entity;

class Branch extends Entity
{
	protected $md_branch_id;
	protected $value;
	protected $name;
	protected $description;
	protected $address;
	protected $phone;
	protected $isactive;
	protected $created_by;
	protected $updated_by;
	protected $leader_id;

	protected $dates   = [
		'created_at',
		'updated_at',
		'deleted_at',
	];

	public function getBranchId()
	{
		return $this->attributes['md_branch_id'];
	}

	public function setBranchId($md_branch_id)
	{
		$this->attributes['md_branch_id'] = $md_branch_id;
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

	public function getAddress()
	{
		return $this->attributes['address'];
	}

	public function setAddress($address)
	{
		$this->attributes['address'] = $address;
	}

	public function getPhone()
	{
		return $this->attributes['phone'];
	}

	public function setPhone($phone)
	{
		$this->attributes['phone'] = $phone;
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

	public function getLeaderId()
	{
		return $this->attributes['leader_id'];
	}

	public function setLeaderId($leader_id)
	{
		$this->attributes['leader_id'] = $leader_id;
	}
}

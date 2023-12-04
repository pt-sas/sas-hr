<?php

namespace App\Entities;

use CodeIgniter\Entity;

class Menu extends Entity
{
	protected $sys_menu_id;
	protected $name;
	protected $url;
	protected $sequence;
	protected $icon;
	protected $initialcode;
	protected $created_by;
	protected $updated_by;
	protected $action;

	protected $dates   = [
		'created_at',
		'updated_at',
		'deleted_at',
	];

	public function getMenuId()
	{
		return $this->attributes['sys_menu_id'];
	}

	public function setMenuId($sys_menu_id)
	{
		$this->attributes['sys_menu_id'] = $sys_menu_id;
	}

	public function getName()
	{
		return $this->attributes['name'];
	}

	public function setName($name)
	{
		$this->attributes['name'] = $name;
	}

	public function getUrl()
	{
		return $this->attributes['url'];
	}

	public function setUrl($url)
	{
		$this->attributes['url'] = $url;
	}

	public function getSequence()
	{
		return $this->attributes['sequence'];
	}

	public function setSequence($sequence)
	{
		$this->attributes['sequence'] = $sequence;
	}

	public function getIcon()
	{
		return $this->attributes['icon'];
	}

	public function setIcon($icon)
	{
		$this->attributes['icon'] = $icon;
	}

	public function getInitialcode()
	{
		return $this->attributes['initialcode'];
	}

	public function setInitialcode($initialcode)
	{
		$this->attributes['initialcode'] = $initialcode;
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

	public function getAction()
	{
		return $this->attributes['action'];
	}

	public function setAction($action)
	{
		$this->attributes['action'] = $action;
	}
}

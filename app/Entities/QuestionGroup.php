<?php

namespace App\Entities;

use CodeIgniter\Entity;

class QuestionGroup extends Entity
{
	protected $md_question_group_id;
	protected $value;
	protected $name;
	protected $description;
	protected $isactive;
	protected $sequence;
	protected $menu_url;
	protected $created_by;
	protected $updated_by;

	protected $dates   = [
		'created_at',
		'updated_at',
		'deleted_at',
	];

	public function getQuestionGroupId()
	{
		return $this->attributes['md_question_group_id'];
	}

	public function setQuestionGroupId($md_question_group_id)
	{
		$this->attributes['md_question_group_id'] = $md_question_group_id;
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

	public function getSequence()
	{
		return $this->attributes['sequence'];
	}

	public function setSequence($sequence)
	{
		$this->attributes['sequence'] = $sequence;
	}

	public function getMenuUrl()
	{
		return $this->attributes['menu_url'];
	}

	public function setMenuUrl($menu_url)
	{
		$this->attributes['menu_url'] = $menu_url;
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

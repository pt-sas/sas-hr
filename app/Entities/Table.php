<?php

namespace App\Entities;

use CodeIgniter\Entity;

class Table extends Entity
{
	protected $primaryKey;
	protected $name;
	protected $type;
	protected $class;
	protected $isrequired;
	protected $isreadonly;
	protected $ischecked;
	protected $list;
	protected $id;
	protected $value;
	protected $length;
	protected $field;
	protected $attribute;
	protected $status;
	protected $title;
	protected $table;

	public function getPrimaryKey()
	{
		if (empty($this->attributes['primaryKey']))
			return null;

		return $this->attributes['primaryKey'];
	}

	public function setPrimaryKey(string $primaryKey)
	{
		$this->attributes['primaryKey'] = $primaryKey;
	}

	public function getName()
	{
		if (empty($this->attributes['name']))
			return null;

		return $this->attributes['name'];
	}

	public function setName(string $name)
	{
		$this->attributes['name'] = $name;
	}

	public function getType()
	{
		if (empty($this->attributes['type']))
			return null;

		return $this->attributes['type'];
	}

	public function setType(string $type)
	{
		$this->attributes['type'] = $type;
	}

	public function getClass()
	{
		if (empty($this->attributes['class']))
			return null;

		return $this->attributes['class'];
	}

	public function setClass($class)
	{
		$this->attributes['class'] = $class;
	}

	public function getIsRequired()
	{
		if (empty($this->attributes['isrequired']) || !$this->attributes['isrequired'])
			return null;

		return "required";
	}

	public function setIsRequired(bool $isrequired)
	{
		$this->attributes['isrequired'] = $isrequired;
	}

	public function getIsReadonly()
	{
		if (empty($this->attributes['isreadonly']))
			return null;

		if ($this->attributes['isreadonly'] && ($this->getType() === "select" || $this->getType() === "checkbox" || $this->getClass() === "datepicker" || $this->getType() === "yearpicker"))
			return "disabled";

		return "readonly";
	}

	public function setIsReadonly(bool $isreadonly)
	{
		$this->attributes['isreadonly'] = $isreadonly;
	}

	public function getIsChecked()
	{
		if ((empty($this->attributes['ischecked']) || !$this->attributes['ischecked']) && $this->getType() === "checkbox" && $this->getValue() !== "Y")
			return null;

		if ($this->getType() === "checkbox" && ($this->getValue() === "Y" || $this->attributes['ischecked']))
			return "checked";
	}

	public function setIsChecked($ischecked)
	{
		$this->attributes['ischecked'] = $ischecked;
	}

	public function getList()
	{
		if (empty($this->attributes['list']))
			return [];

		return $this->attributes['list'];
	}

	public function setList(array $list)
	{
		$this->attributes['list'] = $list;
	}

	public function getId()
	{
		if (empty($this->attributes['id']))
			return null;

		return $this->attributes['id'];
	}

	public function setId($id)
	{
		$this->attributes['id'] = $id;
	}

	public function getValue()
	{
		if (empty($this->attributes['value']))
			return null;

		return $this->attributes['value'];
	}

	public function setValue($value)
	{
		$this->attributes['value'] = $value;
	}

	public function getLength()
	{
		if (empty($this->attributes['length']))
			return 50;

		return $this->attributes['length'];
	}

	public function setLength($length)
	{
		$this->attributes['length'] = $length;
	}

	public function getField()
	{
		if (empty($this->attributes['field']))
			return null;

		return $this->attributes['field'];
	}

	public function setField($field)
	{
		$this->attributes['field'] = $field;
	}

	public function getAttribute()
	{
		if (empty($this->attributes['attribute']))
			return [];

		return $this->attributes['attribute'];
	}

	public function setAttribute(array $attribute)
	{
		$this->attributes['attribute'] = $attribute;
	}

	public function getStatus()
	{
		if (empty($this->attributes['status']))
			return null;

		return $this->attributes['status'];
	}

	public function setStatus(string $status)
	{
		$this->attributes['status'] = $status;
	}

	public function getTitle()
	{
		if (empty($this->attributes['title']))
			return null;

		return $this->attributes['title'];
	}

	public function setTitle(string $title)
	{
		$this->attributes['title'] = $title;
	}

	public function getTable()
	{
		if (empty($this->attributes['table']))
			return null;

		return $this->attributes['table'];
	}

	public function setTable($table)
	{
		$this->attributes['table'] = $table;
	}

	public function getQuery()
	{
		if (empty($this->attributes['query']))
			return null;

		return $this->attributes['query'];
	}

	public function setQuery($query)
	{
		$this->attributes['query'] = $query;
	}
}

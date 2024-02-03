<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Rule extends Entity
{
    protected $md_rule_id;
    protected $name;
    protected $condition;
    protected $value;
    protected $menu_id;
    protected $priority;
    protected $isdetail;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getRuleId()
    {
        return $this->attributes['md_rule_id'];
    }

    public function setRuleId($md_rule_id)
    {
        $this->attributes['md_rule_id'] = $md_rule_id;
    }

    public function getName()
    {
        return $this->attributes['name'];
    }

    public function setName($name)
    {
        $this->attributes['name'] = $name;
    }

    public function getCondition()
    {
        return $this->attributes['condition'];
    }

    public function setCondition($condition)
    {
        $this->attributes['condition'] = $condition;
    }

    public function getValue()
    {
        return $this->attributes['value'];
    }

    public function setValue($value)
    {
        $this->attributes['value'] = $value;
    }

    public function getMenuUrl()
    {
        return $this->attributes['menu_url'];
    }

    public function setMenuUrl($menu_url)
    {
        $this->attributes['menu_url'] = $menu_url;
    }

    public function getPriority()
    {
        return $this->attributes['priority'];
    }

    public function setPriority($priority)
    {
        $this->attributes['priority'] = $priority;
    }

    public function getIsDetail()
    {
        return $this->attributes['isdetail'];
    }

    public function setIsDetail($isdetail)
    {
        $this->attributes['isdetail'] = $isdetail;
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

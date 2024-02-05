<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class RuleValue extends Entity
{
    protected $md_rule_value_id;
    protected $md_rule_detail_id;
    protected $name;
    protected $value;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getRuleValueId()
    {
        return $this->attributes['md_rule_value_id'];
    }

    public function setRuleValueId($md_rule_value_id)
    {
        $this->attributes['md_rule_value_id'] = $md_rule_value_id;
    }

    public function getRuleDetailId()
    {
        return $this->attributes['md_rule_detail_id'];
    }

    public function setRuleDetailId($md_rule_detail_id)
    {
        $this->attributes['md_rule_detail_id'] = $md_rule_detail_id;
    }

    public function getName()
    {
        return $this->attributes['name'];
    }

    public function setName($name)
    {
        $this->attributes['name'] = $name;
    }

    public function getValue()
    {
        return $this->attributes['value'];
    }

    public function setValue($value)
    {
        $this->attributes['value'] = $value;
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

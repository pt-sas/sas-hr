<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class RuleDetail extends Entity
{
    protected $md_rule_detail_id;
    protected $md_rule_id;
    protected $name;
    protected $detail;
    protected $operation;
    protected $format_condition;
    protected $condition;
    protected $format_value;
    protected $value;
    protected $isdetail;
    protected $description;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

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

    public function getOperation()
    {
        return $this->attributes['operation'];
    }

    public function setOperation($operation)
    {
        $this->attributes['operation'] = $operation;
    }

    public function getFormatCondition()
    {
        return $this->attributes['format_condition'];
    }

    public function setFormatCondition($format_condition)
    {
        $this->attributes['format_condition'] = $format_condition;
    }

    public function getCondition()
    {
        return $this->attributes['condition'];
    }

    public function setCondition($condition)
    {
        $this->attributes['condition'] = $condition;
    }

    public function getFormatValue()
    {
        return $this->attributes['format_value'];
    }

    public function setFormatValue($format_value)
    {
        $this->attributes['format_value'] = $format_value;
    }

    public function getValue()
    {
        return $this->attributes['value'];
    }

    public function setValue($value)
    {
        $this->attributes['value'] = $value;
    }

    public function getIsDetail()
    {
        return $this->attributes['isdetail'];
    }

    public function setIsDetail($isdetail)
    {
        $this->attributes['isdetail'] = $isdetail;
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

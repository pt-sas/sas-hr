<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class ReferenceDetail extends Entity
{
    protected $sys_ref_detail_id;
    protected $value;
    protected $name;
    protected $description;
    protected $sys_reference_id;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getRefDetailId()
    {
        return $this->attributes['sys_ref_detail_id'];
    }

    public function setRefDetailId($sys_ref_detail_id)
    {
        $this->attributes['sys_ref_detail_id'] = $sys_ref_detail_id;
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

    public function getReferenceId()
    {
        return $this->attributes['sys_reference_id'];
    }

    public function setReferenceId($sys_reference_id)
    {
        $this->attributes['sys_reference_id'] = $sys_reference_id;
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

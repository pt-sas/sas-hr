<?php

namespace App\Entities;

use CodeIgniter\Entity;

class LeaveType extends Entity
{
    protected $md_leavetype_id;
    protected $value;
    protected $name;
    protected $gender;
    protected $duration;
    protected $duration_type;
    protected $description;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at'
    ];

    public function getLeaveTypeId()
    {
        return $this->attributes['md_leavetype_id'];
    }

    public function setLeaveTypeId($md_leavetype_id)
    {
        $this->attributes['md_leavetype_id'] = $md_leavetype_id;
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

    public function getGender()
    {
        return $this->attributes['gender'];
    }

    public function setGender($gender)
    {
        $this->attributes['gender'] = $gender;
    }

    public function getDuration()
    {
        return $this->attributes['duration'];
    }

    public function setDuration($duration)
    {
        $this->attributes['duration'] = $duration;
    }

    public function getDurationType()
    {
        return $this->attributes['duration_type'];
    }

    public function setDurationType($duration_type)
    {
        $this->attributes['duration_type'] = $duration_type;
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
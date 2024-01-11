<?php

namespace App\Entities;

use CodeIgniter\Entity;

class MassLeave extends Entity
{
    protected $md_massleave_id;
    protected $name;
    protected $description;
    protected $isactive;
    protected $created_by;
    protected $updated_by;
    protected $startdate;
    protected $enddate;
    protected $isaffect;


    protected $dates   = [
        'created_at',
        'updated_at'
    ];

    public function getMassLeaveId()
    {
        return $this->attributes['md_massleave_id'];
    }

    public function setMassLeaveId($md_massleave_id)
    {
        $this->attributes['md_massleave_id'] = $md_massleave_id;
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

    public function getStartDate()
    {
        return $this->attributes['startdate'];
    }

    public function setStartDate($startdate)
    {
        $this->attributes['startdate'] = $startdate;
    }

    public function getEndDate()
    {
        return $this->attributes['enddate'];
    }

    public function setEndDate($enddate)
    {
        $this->attributes['enddate'] = $enddate;
    }

    public function getIsAffect()
    {
        return $this->attributes['isaffect'];
    }

    public function setIsAffect($isaffect)
    {
        return $this->attributes['isaffect'] = $isaffect;
    }
}

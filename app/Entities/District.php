<?php

namespace App\Entities;

use CodeIgniter\Entity;

class District extends Entity
{
    protected $md_district_id;
    protected $value;
    protected $name;
    protected $md_city_id;
    protected $description;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates = [
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function getDistrictId()
    {
        return $this->attributes['md_district_id'];
    }

    public function setDisctrictId($md_district_id)
    {
        $this->attributes['md_district_id'] = $md_district_id;
    }

    public function getCityId()
    {
        return $this->attributes['md_city_id'];
    }

    public function setCityId($md_city_id)
    {
        $this->attributes['md_city_id'] = $md_city_id;
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

    public function getIsActive()
    {
        return $this->attributes['isactive'];
    }

    public function setIsActive($isactive)
    {
        $this->attributes['isactive'] = $isactive;
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

<?php

namespace App\Entities;

use CodeIgniter\Entity;

class Province extends Entity
{
    protected $md_province_id;
    protected $value;
    protected $name;
    protected $description;
    protected $md_country_id;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
    ];

    public function getProvinceId()
    {
        return $this->attributes['md_province_id'];
    }

    public function setProvinceId($md_province_id)
    {
        $this->attributes['md_province_id'] = $md_province_id;
    }

    public function getCountryId()
    {
        return $this->attributes['md_country_id'];
    }

    public function setCountryId($md_country_id)
    {
        $this->attributes['md_country_id'] = $md_country_id;
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

<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class EmpContact extends Entity
{
    protected $md_employee_contact_id;
    protected $md_employee_id;
    protected $number;
    protected $name;
    protected $phone;
    protected $description;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getEmpContactId()
    {
        return $this->attributes['md_employee_contact_id'];
    }

    public function setEmpContactId($md_employee_contact_id)
    {
        $this->attributes['md_employee_contact_id'] = $md_employee_contact_id;
    }

    public function getEmployeeId()
    {
        return $this->attributes['md_employee_id'];
    }

    public function setEmployeeId($md_employee_id)
    {
        $this->attributes['md_employee_id'] = $md_employee_id;
    }

    public function getMember()
    {
        return $this->attributes['member'];
    }

    public function setMember($member)
    {
        $this->attributes['member'] = $member;
    }

    public function getName()
    {
        return $this->attributes['name'];
    }

    public function setName($name)
    {
        $this->attributes['name'] = $name;
    }

    public function getPhone()
    {
        return $this->attributes['phone'];
    }

    public function setPhone($phone)
    {
        $this->attributes['phone'] = $phone;
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

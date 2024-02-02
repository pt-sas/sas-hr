<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class EmpFamilyCore extends Entity
{
    protected $md_employee_family_core_id;
    protected $md_employee_id;
    protected $member;
    protected $name;
    protected $gender;
    protected $age;
    protected $education;
    protected $job;
    protected $status;
    protected $dateofdeath;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getEmpFamilyCoreId()
    {
        return $this->attributes['md_employee_family_core_id'];
    }

    public function setEmpFamilyCoreId($md_employee_family_core_id)
    {
        $this->attributes['md_employee_family_core_id'] = $md_employee_family_core_id;
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

    public function getGender()
    {
        return $this->attributes['gender'];
    }

    public function setGender($gender)
    {
        $this->attributes['gender'] = $gender;
    }

    public function getAge()
    {
        return $this->attributes['age'];
    }

    public function setAge($age)
    {
        $this->attributes['age'] = $age;
    }

    public function getEducation()
    {
        return $this->attributes['education'];
    }

    public function setEducation($education)
    {
        $this->attributes['education'] = $education;
    }

    public function getJob()
    {
        return $this->attributes['job'];
    }

    public function setJob($job)
    {
        $this->attributes['job'] = $job;
    }

    public function getStatus()
    {
        return $this->attributes['status'];
    }

    public function setStatus($status)
    {
        $this->attributes['status'] = $status;
    }

    public function getDateOfDeath()
    {
        return $this->attributes['dateofdeath'];
    }

    public function setDateOfDeath($dateofdeath)
    {
        $this->attributes['dateofdeath'] = $dateofdeath;
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

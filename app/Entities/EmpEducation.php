<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class EmpEducation extends Entity
{
    protected $md_employee_education_id;
    protected $md_employee_id;
    protected $education;
    protected $scholl;
    protected $city;
    protected $startyear;
    protected $endyear;
    protected $major;
    protected $status;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getEmpEducationId()
    {
        return $this->attributes['md_employee_education_id'];
    }

    public function setEmpEducationId($md_employee_education_id)
    {
        $this->attributes['md_employee_education_id'] = $md_employee_education_id;
    }

    public function getEmployeeId()
    {
        return $this->attributes['md_employee_id'];
    }

    public function setEmployeeId($md_employee_id)
    {
        $this->attributes['md_employee_id'] = $md_employee_id;
    }

    public function getEducation()
    {
        return $this->attributes['education'];
    }

    public function setEducation($education)
    {
        $this->attributes['education'] = $education;
    }

    public function getSchool()
    {
        return $this->attributes['school'];
    }

    public function setSchool($school)
    {
        $this->attributes['school'] = $school;
    }

    public function getCity()
    {
        return $this->attributes['city'];
    }

    public function setCity($city)
    {
        $this->attributes['city'] = $city;
    }

    public function getStartYear()
    {
        return $this->attributes['startyear'];
    }

    public function setStartYear($startyear)
    {
        $this->attributes['startyear'] = $startyear;
    }

    public function getEndYear()
    {
        return $this->attributes['endyear'];
    }

    public function setEndYear($endyear)
    {
        $this->attributes['endyear'] = $endyear;
    }

    public function getMajor()
    {
        return $this->attributes['major'];
    }

    public function setMajor($major)
    {
        $this->attributes['major'] = $major;
    }

    public function setStatus($status)
    {
        $this->attributes['status'] = $status;
    }

    public function getStatus()
    {
        return $this->attributes['status'];
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

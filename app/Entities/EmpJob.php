<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class EmpJob extends Entity
{
    protected $md_employee_job_id;
    protected $md_employee_id;
    protected $company;
    protected $startdate;
    protected $enddate;
    protected $position;
    protected $salary;
    protected $reason;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getEmpJobId()
    {
        return $this->attributes['md_employee_job_id'];
    }

    public function setEmpJobId($md_employee_job_id)
    {
        $this->attributes['md_employee_job_id'] = $md_employee_job_id;
    }

    public function getEmployeeId()
    {
        return $this->attributes['md_employee_id'];
    }

    public function setEmployeeId($md_employee_id)
    {
        $this->attributes['md_employee_id'] = $md_employee_id;
    }

    public function getCompany()
    {
        return $this->attributes['company'];
    }

    public function setCompany($company)
    {
        $this->attributes['company'] = $company;
    }

    public function getStartDate()
    {
        if (!empty($this->attributes['startdate']))
            return format_dmy($this->attributes['startdate'], "-");

        return $this->attributes['startdate'];
    }

    public function setStartDate($startdate)
    {
        $this->attributes['startdate'] = $startdate;
    }

    public function getEndDate()
    {
        if (!empty($this->attributes['enddate']))
            return format_dmy($this->attributes['enddate'], "-");

        return $this->attributes['enddate'];
    }

    public function setEndDate($enddate)
    {
        $this->attributes['enddate'] = $enddate;
    }

    public function getPosition()
    {
        return $this->attributes['position'];
    }

    public function setPosition($position)
    {
        $this->attributes['position'] = $position;
    }

    public function getSalary()
    {
        return $this->attributes['salary'];
    }

    public function setSalary($salary)
    {
        $this->attributes['salary'] = $salary;
    }

    public function getReason()
    {
        return $this->attributes['reason'];
    }

    public function setReason($reason)
    {
        $this->attributes['reason'] = $reason;
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

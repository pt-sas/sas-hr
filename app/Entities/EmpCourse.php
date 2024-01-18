<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class EmpCourse extends Entity
{
    protected $md_employee_courses_id;
    protected $md_employee_id;
    protected $course;
    protected $intitution;
    protected $level;
    protected $startdate;
    protected $enddate;
    protected $status;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getEmpCoursesId()
    {
        return $this->attributes['md_employee_courses_id'];
    }

    public function setEmpCoursesId($md_employee_courses_id)
    {
        $this->attributes['md_employee_courses_id'] = $md_employee_courses_id;
    }

    public function getEmployeeId()
    {
        return $this->attributes['md_employee_id'];
    }

    public function setEmployeeId($md_employee_id)
    {
        $this->attributes['md_employee_id'] = $md_employee_id;
    }

    public function getCourse()
    {
        return $this->attributes['course'];
    }

    public function setCourse($course)
    {
        $this->attributes['course'] = $course;
    }

    public function getIntitution()
    {
        return $this->attributes['intitution'];
    }

    public function setIntitution($intitution)
    {
        $this->attributes['intitution'] = $intitution;
    }

    public function getLevel()
    {
        return $this->attributes['level'];
    }

    public function setLevel($level)
    {
        $this->attributes['level'] = $level;
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

    public function getStatus()
    {
        return $this->attributes['status'];
    }

    public function setStatus($status)
    {
        $this->attributes['status'] = $status;
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

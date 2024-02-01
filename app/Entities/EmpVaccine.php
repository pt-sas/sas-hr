<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class EmpVaccine extends Entity
{
    protected $md_employee_vaccine_id;
    protected $md_employee_id;
    protected $vaccinetype;
    protected $vaccinedate;
    protected $description;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getEmpVaccineId()
    {
        return $this->attributes['md_employee_vaccine_id'];
    }

    public function setEmpVaccineId($md_employee_vaccine_id)
    {
        $this->attributes['md_employee_vaccine_id'] = $md_employee_vaccine_id;
    }

    public function getEmployeeId()
    {
        return $this->attributes['md_employee_id'];
    }

    public function setEmployeeId($md_employee_id)
    {
        $this->attributes['md_employee_id'] = $md_employee_id;
    }

    public function getVaccineType()
    {
        return $this->attributes['vaccinetype'];
    }

    public function setVaccineType($vaccinetype)
    {
        $this->attributes['vaccinetype'] = $vaccinetype;
    }

    public function getVaccineDate()
    {
        return $this->attributes['vaccinedate'];
    }

    public function setVaccineDate($vaccinedate)
    {
        $this->attributes['vaccinedate'] = $vaccinedate;
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

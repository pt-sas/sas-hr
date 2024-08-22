<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class EmpBenefit extends Entity
{
    protected $md_employee_benefit_id;
    protected $md_employee_id;
    protected $benefit;
    protected $status;
    protected $isdetail;
    protected $description;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getEmpBenefitId()
    {
        return $this->attributes['md_employee_benefit_id'];
    }

    public function setEmpBenefitId($md_employee_benefit_id)
    {
        $this->attributes['md_employee_benefit_id'] = $md_employee_benefit_id;
    }

    public function getEmployeeId()
    {
        return $this->attributes['md_employee_id'];
    }

    public function setEmployeeId($md_employee_id)
    {
        $this->attributes['md_employee_id'] = $md_employee_id;
    }

    public function getBenefit()
    {
        return $this->attributes['benefit'];
    }

    public function setBenefit($benefit)
    {
        $this->attributes['benefit'] = $benefit;
    }

    public function getStatus()
    {
        return $this->attributes['status'];
    }

    public function setStatus($status)
    {
        $this->attributes['status'] = $status;
    }

    public function getIsDetail()
    {
        return $this->attributes['isdetail'];
    }

    public function setIsDetail($isdetail)
    {
        $this->attributes['isdetail'] = $isdetail;
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
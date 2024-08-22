<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class BenefitDetail extends Entity
{
    protected $md_employee_benefit_detail_id;
    protected $md_employee_benefit_id;
    protected $benefit_detail;
    protected $status;
    protected $description;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getBenefitDetailId()
    {
        return $this->attributes['md_employee_benefit_detail_id'];
    }

    public function setBenefitDetailId($md_employee_benefit_detail_id)
    {
        $this->attributes['md_employee_benefit_detail_id'] = $md_employee_benefit_detail_id;
    }

    public function getEmployeeBenefitId()
    {
        return $this->attributes['md_employee_benefit_id'];
    }

    public function setEmployeeBenefitId($md_employee_benefit_id)
    {
        $this->attributes['md_employee_benefit_id'] = $md_employee_benefit_id;
    }

    public function getBenefitDetail()
    {
        return $this->attributes['benefit_detail'];
    }

    public function setBenefitDetail($benefit_detail)
    {
        $this->attributes['benefit_detail'] = $benefit_detail;
    }

    public function getStatus()
    {
        return $this->attributes['status'];
    }

    public function setStatus($status)
    {
        $this->attributes['status'] = $status;
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
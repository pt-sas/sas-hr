<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class BenefitLine extends Entity
{
    protected $md_benefit_detail_id;
    protected $md_benefit_id;
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

    public function getBenefitDetailId()
    {
        return $this->attributes['md_benefit_detail_id'];
    }

    public function setBenefitDetailId($md_benefit_detail_id)
    {
        $this->attributes['md_benefit_detail_id'] = $md_benefit_detail_id;
    }

    public function getBenefitId()
    {
        return $this->attributes['md_benefit_id'];
    }

    public function setBenefitId($md_benefit_id)
    {
        $this->attributes['md_benefit_id'] = $md_benefit_id;
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

    public function getDescription()
    {
        return $this->attributes['description'];
    }

    public function setDescription($description)
    {
        $this->attributes['description'] = $description;
    }

    public function setIsDetail($isdetail)
    {
        $this->attributes['isdetail'] = $isdetail;
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

    public function setCreatedAt($created_at)
    {
        $this->attributes['created_at'] = $created_at;
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

    public function setUpdatedAt($updated_at)
    {
        $this->attributes['updated_at'] = $updated_at;
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

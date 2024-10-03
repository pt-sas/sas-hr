<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Benefit extends Entity
{
    protected $md_benefit_id;
    protected $name;
    protected $md_branch_id;
    protected $md_division_id;
    protected $md_position_id;
    protected $md_levelling_id;
    protected $md_status_id;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getBenefitId()
    {
        return $this->attributes['md_benefit_id'];
    }

    public function setBenefitId($md_benefit_id)
    {
        $this->attributes['md_benefit_id'] = $md_benefit_id;
    }

    public function getName()
    {
        return $this->attributes['name'];
    }

    public function setName($name)
    {
        $this->attributes['name'] = $name;
    }

    public function getBranchId()
    {
        return $this->attributes['md_branch_id'];
    }

    public function setBranchId($md_branch_id)
    {
        $this->attributes['md_branch_id'] = $md_branch_id;
    }

    public function getDivisionId()
    {
        return $this->attributes['md_division_id'];
    }

    public function setDivisionId($md_division_id)
    {
        $this->attributes['md_division_id'] = $md_division_id;
    }

    public function getPositionId()
    {
        return $this->attributes['md_position_id'];
    }

    public function setPositionId($md_position_id)
    {
        $this->attributes['md_position_id'] = $md_position_id;
    }

    public function getLevellingId()
    {
        return $this->attributes['md_levelling_id'];
    }

    public function setLevellingId($md_levelling_id)
    {
        $this->attributes['md_levelling_id'] = $md_levelling_id;
    }

    public function getStatusId()
    {
        return $this->attributes['md_status_id'];
    }

    public function setStatusId($md_status_id)
    {
        $this->attributes['md_status_id'] = $md_status_id;
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

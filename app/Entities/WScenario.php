<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class WScenario extends Entity
{
    protected $sys_wfscenario_id;
    protected $name;
    protected $lineno;
    protected $grandtotal;
    protected $menu;
    protected $md_status_id;
    protected $md_branch_id;
    protected $md_division_id;
    protected $description;
    protected $isactive;
    protected $created_by;
    protected $updated_by;
    protected $md_levelling_id;
    protected $submissiontype;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getWfScenarioId()
    {
        return $this->attributes['sys_wfscenario_id'];
    }

    public function setWfScenarioId($sys_wfscenario_id)
    {
        $this->attributes['sys_wfscenario_id'] = $sys_wfscenario_id;
    }

    public function getName()
    {
        return $this->attributes['name'];
    }

    public function setName($name)
    {
        $this->attributes['name'] = $name;
    }

    public function getLineNo()
    {
        return $this->attributes['lineno'];
    }

    public function setLineNo($lineno)
    {
        $this->attributes['lineno'] = $lineno;
    }

    public function getGrandTotal()
    {
        return $this->attributes['grandtotal'];
    }

    public function setGrandTotal($grandtotal)
    {
        $this->attributes['grandtotal'] = $grandtotal;
    }

    public function getStatusId()
    {
        return $this->attributes['md_status_id'];
    }

    public function setStatusId($md_status_id)
    {
        $this->attributes['md_status_id'] = $md_status_id;
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

    public function getLevellingId()
    {
        return $this->attributes['md_levelling_id'];
    }

    public function setLevellingId($md_levelling_id)
    {
        $this->attributes['md_levelling_id'] = $md_levelling_id;
    }

    public function getSubmissionType()
    {
        return $this->attributes['submissiontype'];
    }

    public function setSubmissionType($submissiontype)
    {
        $this->attributes['submissiontype'] = $submissiontype;
    }
}

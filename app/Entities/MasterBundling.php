<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class MasterBundling extends Entity
{
    protected $trx_master_bundling_id;
    protected $value;
    protected $name;
    protected $md_doctype_id;
    protected $minimum_hour;
    protected $compensation_type;
    protected $period_type;
    protected $project_value;
    protected $submissiontype;
    protected $divisionrule;
    protected $isprorate;
    protected $description;
    protected $docstatus;
    protected $isapproved;
    protected $approveddate;
    protected $sys_wfscenario_id;
    protected $created_by;
    protected $updated_by;

    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];

    public function getMasterBundlingId()
    {
        return $this->attributes['trx_master_bundling_id'];
    }

    public function setMasterBundlingId($trx_master_bundling_id)
    {
        $this->attributes['trx_master_bundling_id'] = $trx_master_bundling_id;
    }

    public function getValue()
    {
        return $this->attributes['value'];
    }

    public function setValue($value)
    {
        $this->attributes['value'] = $value;
    }

    public function getName()
    {
        return $this->attributes['name'];
    }

    public function setName($name)
    {
        $this->attributes['name'] = $name;
    }

    public function getDoctypeId()
    {
        return $this->attributes['md_doctype_id'];
    }

    public function setDoctypeId($md_doctype_id)
    {
        $this->attributes['md_doctype_id'] = $md_doctype_id;
    }

    public function getMinimumHour()
    {
        return $this->attributes['minimum_hour'];
    }

    public function setMinimumHour($minimum_hour)
    {
        $this->attributes['minimum_hour'] = $minimum_hour;
    }

    public function getCompensationType()
    {
        return $this->attributes['compensation_type'];
    }

    public function setCompensationType($compensation_type)
    {
        $this->attributes['compensation_type'] = $compensation_type;
    }

    public function getPeriodType()
    {
        return $this->attributes['period_type'];
    }

    public function setPeriodType($period_type)
    {
        $this->attributes['period_type'] = $period_type;
    }

    public function getProjectValue()
    {
        return $this->attributes['project_value'];
    }

    public function setProjectValue($project_value)
    {
        $this->attributes['project_value'] = $project_value;
    }

    public function getDivisionRule()
    {
        return $this->attributes['divisionrule'];
    }

    public function setDivisionRule($divisionrule)
    {
        $this->attributes['divisionrule'] = $divisionrule;
    }

    public function getIsProrate()
    {
        return $this->attributes['isprorate'];
    }

    public function setIsProrate($isprorate)
    {
        $this->attributes['isprorate'] = $isprorate;
    }

    public function getSubmissionType()
    {
        return $this->attributes['submissiontype'];
    }

    public function setSubmissionType($submissiontype)
    {
        $this->attributes['submissiontype'] = $submissiontype;
    }

    public function getDescription()
    {
        return $this->attributes['description'];
    }

    public function setDescription($description)
    {
        $this->attributes['description'] = $description;
    }

    public function getDocStatus()
    {
        return $this->attributes['docstatus'];
    }

    public function setDocStatus($docstatus)
    {
        $this->attributes['docstatus'] = $docstatus;
    }

    public function getIsApproved()
    {
        return $this->attributes['isapproved'];
    }

    public function setIsApproved($isapproved)
    {
        $this->attributes['isapproved'] = $isapproved;
    }

    public function getApprovedDate()
    {
        return $this->attributes['approveddate'];
    }

    public function setApprovedDate($approveddate)
    {
        $this->attributes['approveddate'] = $approveddate;
    }

    public function getWfScenarioId()
    {
        return $this->attributes['sys_wfscenario_id'];
    }

    public function setWfScenarioId($sys_wfscenario_id)
    {
        $this->attributes['sys_wfscenario_id'] = $sys_wfscenario_id;
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

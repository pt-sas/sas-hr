<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Bundling extends Entity
{
    protected $trx_bundling_id;
    protected $trx_master_bundling_id;
    protected $minimum_hour;
    protected $maximum_hour;
    protected $compensation_type;
    protected $project_value;
    protected $documentno;
    protected $md_employee_id;
    protected $md_branch_id;
    protected $md_division_id;
    protected $submissiontype;
    protected $submissiondate;
    protected $startdate;
    protected $enddate;
    protected $description;
    protected $docstatus;
    protected $isapproved;
    protected $approved_by;
    protected $receiveddate;
    protected $approveddate;
    protected $sys_wfscenario_id;
    protected $created_by;
    protected $updated_by;

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getBundlingId()
    {
        return $this->attributes['trx_bundling_id'];
    }

    public function setBundlingId($trx_bundling_id)
    {
        $this->attributes['trx_bundling_id'] = $trx_bundling_id;
    }

    public function getMasterBundlingId()
    {
        return $this->attributes['trx_master_bundling_id'];
    }

    public function setMasterBundlingId($trx_master_bundling_id)
    {
        $this->attributes['trx_master_bundling_id'] = $trx_master_bundling_id;
    }

    public function getMinimumHour()
    {
        return $this->attributes['minimum_hour'];
    }

    public function setMinimumHour($minimum_hour)
    {
        $this->attributes['minimum_hour'] = $minimum_hour;
    }

    public function getMaximumHour()
    {
        return $this->attributes['maximum_hour'];
    }

    public function setMaximumHour($maximum_hour)
    {
        $this->attributes['maximum_hour'] = $maximum_hour;
    }

    public function getCompensationType()
    {
        return $this->attributes['compensation_type'];
    }

    public function setCompensationType($compensation_type)
    {
        $this->attributes['compensation_type'] = $compensation_type;
    }

    public function getProjectValue()
    {
        return $this->attributes['project_value'];
    }

    public function setProjectValue($project_value)
    {
        $this->attributes['project_value'] = $project_value;
    }

    public function getDocumentNo()
    {
        return $this->attributes['documentno'];
    }

    public function setDocumentNo($documentno)
    {
        $this->attributes['documentno'] = $documentno;
    }

    public function getEmployeeId()
    {
        return $this->attributes['md_employee_id'];
    }

    public function setEmployeeId($md_employee_id)
    {
        $this->attributes['md_employee_id'] = $md_employee_id;
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

    public function getSubmissionType()
    {
        return $this->attributes['submissiontype'];
    }

    public function setSubmissionType($submissiontype)
    {
        $this->attributes['submissiontype'] = $submissiontype;
    }

    public function getSubmissionDate()
    {
        return $this->attributes['submissiondate'];
    }

    public function setSubmissionDate($submissiondate)
    {
        $this->attributes['submissiondate'] = $submissiondate;
    }

    public function getStartDate()
    {
        return $this->attributes['startdate'];
    }

    public function setStartDate($startdate)
    {
        $this->attributes['startdate'] = $startdate;
    }

    public function getEndDate()
    {
        return $this->attributes['enddate'];
    }

    public function setEndDate($enddate)
    {
        $this->attributes['enddate'] = $enddate;
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

    public function getApprovedBy()
    {
        return $this->attributes['approved_by'];
    }

    public function setApprovedBy($approved_by)
    {
        $this->attributes['approved_by'] = $approved_by;
    }

    public function getReceivedDate()
    {
        return $this->attributes['receiveddate'];
    }

    public function setReceivedDate($receiveddate)
    {
        $this->attributes['receiveddate'] = $receiveddate;
    }

    public function getApprovedDate()
    {
        if ($this->attributes['approveddate'] == '0000-00-00 00:00:00') {
            return null;
        }

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

    public function getCreatedBy()
    {
        return $this->attributes['created_by'];
    }

    public function setCreatedBy($created_by)
    {
        $this->attributes['created_by'] = $created_by;
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

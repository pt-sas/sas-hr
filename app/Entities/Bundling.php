<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Bundling extends Entity
{
    protected $trx_bundling_id;
    protected $documentno;
    protected $name;
    protected $bundling_type;
    protected $md_employee_id;
    protected $md_branch_id;
    protected $md_division_id;
    protected $submissiontype;
    protected $submissiondate;
    protected $startdate;
    protected $enddate;
    protected $estimate_time;
    protected $description;
    protected $docstatus;
    protected $isapproved;
    protected $approved_by;
    protected $receiveddate;
    protected $approveddate;
    protected $sys_wfscenario_id;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
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

    public function getDocumentno()
    {
        return $this->attributes['documentno'];
    }

    public function setDocumentno($documentno)
    {
        $this->attributes['documentno'] = $documentno;
    }

    public function getName()
    {
        return $this->attributes['name'];
    }

    public function setName($name)
    {
        $this->attributes['name'] = $name;
    }

    public function getBundlingType()
    {
        return $this->attributes['bundling_type'];
    }

    public function setBundlingType($bundling_type)
    {
        $this->attributes['bundling_type'] = $bundling_type;
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

    public function getSubmissiontype()
    {
        return $this->attributes['submissiontype'];
    }

    public function setSubmissiontype($submissiontype)
    {
        $this->attributes['submissiontype'] = $submissiontype;
    }

    public function getSubmissiondate()
    {
        return $this->attributes['submissiondate'];
    }

    public function setSubmissiondate($submissiondate)
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

    public function getEstimateTime()
    {
        return $this->attributes['estimate_time'];
    }

    public function setEstimateTime($estimate_time)
    {
        $this->attributes['estimate_time'] = $estimate_time;
    }

    public function getDescription()
    {
        return $this->attributes['description'];
    }

    public function setDescription($description)
    {
        $this->attributes['description'] = $description;
    }

    public function getDocstatus()
    {
        return $this->attributes['docstatus'];
    }

    public function setDocstatus($docstatus)
    {
        $this->attributes['docstatus'] = $docstatus;
    }

    public function getIsapproved()
    {
        return $this->attributes['isapproved'];
    }

    public function setIsapproved($isapproved)
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

    public function getReceiveddate()
    {
        return $this->attributes['receiveddate'];
    }

    public function setReceiveddate($receiveddate)
    {
        $this->attributes['receiveddate'] = $receiveddate;
    }

    public function getApproveddate()
    {
        if ($this->attributes['approveddate'] == '0000-00-00 00:00:00')
            return null;

        return $this->attributes['approveddate'];
    }

    public function setApproveddate($approveddate)
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

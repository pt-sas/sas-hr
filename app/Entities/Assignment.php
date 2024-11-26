<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Assignment extends Entity
{
    protected $trx_assignment_id;
    protected $documentno;
    protected $md_employee_id;
    protected $md_branch_id;
    protected $md_division_id;
    protected $branch_to;
    protected $submissiontype;
    protected $submissiondate;
    protected $startdate;
    protected $enddate;
    protected $reason;
    protected $docstatus;
    protected $isapproved;
    protected $receiveddate;
    protected $approveddate;
    protected $sys_wfscenario_id;
    protected $branch_in;
    protected $branch_out;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getAssignmentId()
    {
        return $this->attributes['trx_assignment_id'];
    }

    public function setAssignmentId($trx_assignment_id)
    {
        $this->attributes['trx_assignment_id'] = $trx_assignment_id;
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

    public function getBranchIn()
    {
        return $this->attributes['branch_in'];
    }

    public function setBranchIn($branch_in)
    {
        $this->attributes['branch_in'] = $branch_in;
    }

    public function getBranchOut()
    {
        return $this->attributes['branch_out'];
    }

    public function setBranchOut($branch_out)
    {
        $this->attributes['branch_out'] = $branch_out;
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
        // if (!empty($this->attributes['startdate']))
        // return format_dmy($this->attributes['startdate'], "-");

        return $this->attributes['startdate'];
    }

    public function setStartDate($startdate)
    {
        $this->attributes['startdate'] = $startdate;
    }

    public function getEndDate()
    {
        // if (!empty($this->attributes['enddate']))
        //     return format_dmy($this->attributes['enddate'], "-");

        return $this->attributes['enddate'];
    }

    public function setEndDate($enddate)
    {
        $this->attributes['enddate'] = $enddate;
    }

    public function getReason()
    {
        return $this->attributes['reason'];
    }

    public function setReason($reason)
    {
        $this->attributes['reason'] = $reason;
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

    public function getReceivedDate()
    {
        if (!empty($this->attributes['receiveddate']))
            return format_dmy($this->attributes['receiveddate'], "-");

        return $this->attributes['receiveddate'];
    }

    public function setReceivedDate($receiveddate)
    {
        if (empty($this->attributes['receiveddate']))
            return null;

        $this->attributes['receiveddate'] = $receiveddate;
    }

    public function getApprovedDate()
    {
        if (!empty($this->attributes['approveddate']))
            return format_dmy($this->attributes['approveddate'], "-");

        return $this->attributes['approveddate'];
    }

    public function setApprovedDate($approveddate)
    {
        if (empty($this->attributes['approveddate']))
            return null;

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

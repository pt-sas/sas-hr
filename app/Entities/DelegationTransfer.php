<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class DelegationTransfer extends Entity
{
    protected $trx_delegation_transfer_id;
    protected $documentno;
    protected $employee_from;
    protected $employee_to;
    protected $md_branch_id;
    protected $md_division_id;
    protected $submissiontype;
    protected $submissiondate;
    protected $date;
    protected $reason;
    protected $docstatus;
    protected $isapproved;
    protected $receiveddate;
    protected $approveddate;
    protected $sys_wfscenario_id;
    protected $created_by;
    protected $updated_by;
    protected $approved_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getDelegationTransferId()
    {
        return $this->attributes['trx_delegation_transfer_id'];
    }

    public function setDelegationTransferId($trx_delegation_transfer_id)
    {
        $this->attributes['trx_delegation_transfer_id'] = $trx_delegation_transfer_id;
    }

    public function getDocumentNo()
    {
        return $this->attributes['documentno'];
    }

    public function setDocumentNo($documentno)
    {
        $this->attributes['documentno'] = $documentno;
    }

    public function getEmployeeFrom()
    {
        return $this->attributes['employee_from'];
    }

    public function setEmployeeFrom($employee_from)
    {
        $this->attributes['employee_from'] = $employee_from;
    }

    public function getEmployeeTo()
    {
        return $this->attributes['employee_to'];
    }

    public function setEmployeeTo($employee_to)
    {
        $this->attributes['employee_to'] = $employee_to;
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
    public function getDate()
    {
        return $this->attributes['date'];
    }

    public function setDate($date)
    {
        $this->attributes['date'] = $date;
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
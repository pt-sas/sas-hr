<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Adjustment extends Entity
{
    protected $trx_adjustment_id;
    protected $documentno;
    protected $md_employee_id;
    protected $md_branch_id;
    protected $md_division_id;
    protected $submissiontype;
    protected $submissiondate;
    protected $begin_balance;
    protected $adjustment;
    protected $ending_balance;
    protected $date;
    protected $md_year_id;
    protected $reason;
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

    public function getAdjustmentId()
    {
        return $this->attributes['trx_adjustment_id'];
    }

    public function setAdjustmentId($trx_adjustment_id)
    {
        $this->attributes['trx_adjustment_id'] = $trx_adjustment_id;
    }

    public function getDocumentno()
    {
        return $this->attributes['documentno'];
    }

    public function setDocumentno($documentno)
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

    public function getBeginBalance()
    {
        return $this->attributes['begin_balance'];
    }

    public function setBeginBalance($begin_balance)
    {
        $this->attributes['begin_balance'] = $begin_balance;
    }

    public function getAdjustment()
    {
        return $this->attributes['adjustment'];
    }

    public function setAdjustment($adjustment)
    {
        $this->attributes['adjustment'] = $adjustment;
    }

    public function getEndingBalance()
    {
        return $this->attributes['ending_balance'];
    }

    public function setEndingBalance($ending_balance)
    {
        $this->attributes['ending_balance'] = $ending_balance;
    }

    public function getDate()
    {
        return $this->attributes['date'];
    }

    public function setDate($date)
    {
        $this->attributes['date'] = $date;
    }

    public function getYear()
    {
        return $this->attributes['md_year_id'];
    }

    public function setYear($md_year_id)
    {
        $this->attributes['md_year_id'] = $md_year_id;
    }

    public function getReason()
    {
        return $this->attributes['reason'];
    }

    public function setReason($reason)
    {
        $this->attributes['reason'] = $reason;
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

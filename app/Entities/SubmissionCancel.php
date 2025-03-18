<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class SubmissionCancel extends Entity
{
    protected $trx_submission_cancel_id;
    protected $documentno;
    protected $md_employee_id;
    protected $md_branch_id;
    protected $md_division_id;
    protected $submissiondate;
    protected $receiveddate;
    protected $submissiontype;
    protected $ref_submissiontype;
    protected $reason;
    protected $docstatus;
    protected $image;
    protected $isapproved;
    protected $approveddate;
    protected $sys_wfscenario_id;
    protected $isactive;
    protected $created_by;
    protected $updated_by;
    protected $reference_id;
    protected $ref_table;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getSubmissionCancelId()
    {
        return $this->attributes['trx_submission_cancel_id'];
    }

    public function setSubmissionCancelId($trx_submission_cancel_id)
    {
        $this->attributes['trx_submission_cancel_id'] = $trx_submission_cancel_id;
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

    public function getSubmissionDate()
    {
        return $this->attributes['submissiondate'];
    }

    public function setSubmissionDate($submissiondate)
    {
        $this->attributes['submissiondate'] = $submissiondate;
    }

    public function getReceivedDate()
    {
        return $this->attributes['receiveddate'];
    }

    public function setReceivedDate($receiveddate)
    {
        if (empty($receiveddate) || isset($this->attributes['receiveddate']))
            return null;

        $this->attributes['receiveddate'] = $receiveddate;
    }


    public function getSubmissionType()
    {
        return $this->attributes['submissiontype'];
    }

    public function setSubmissionType($submissiontype)
    {
        $this->attributes['submissiontype'] = $submissiontype;
    }

    public function getRefSubmissionType()
    {
        return $this->attributes['ref_submissiontype'];
    }

    public function setRefSubmissionType($ref_submissiontype)
    {
        $this->attributes['ref_submissiontype'] = $ref_submissiontype;
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

    public function getImage()
    {
        return $this->attributes['image'];
    }

    public function setImage($image)
    {
        $this->attributes['image'] = $image;
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

    public function getReferenceId()
    {
        return $this->attributes['reference_id'];
    }
    public function setReferenceId($reference_id)
    {
        $this->attributes['reference_id'] = $reference_id;
    }

    public function getRefTable()
    {
        return $this->attributes['ref_table'];
    }
    public function setRefTable($ref_table)
    {
        $this->attributes['ref_table'] = $ref_table;
    }
}
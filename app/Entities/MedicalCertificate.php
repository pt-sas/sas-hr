<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class MedicalCertificate extends Entity
{
    protected $trx_medical_certificate_id;
    protected $documentno;
    protected $trx_absent_id;
    protected $md_employee_id;
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
    protected $pdf;
    protected $approved_by;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getMedicalCertificateId()
    {
        return $this->attributes['trx_medical_certificate_id'];
    }

    public function setMedicalCertificateId($trx_medical_certificate_id)
    {
        $this->attributes['trx_medical_certificate_id'] = $trx_medical_certificate_id;
    }

    public function getAbsentId()
    {
        return $this->attributes['trx_absent_id'];
    }

    public function setAbsentId($trx_absent_id)
    {
        $this->attributes['trx_absent_id'] = $trx_absent_id;
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

    public function getDate()
    {
        // if (!empty($this->attributes['startdate']))
        // return format_dmy($this->attributes['startdate'], "-");

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

    public function getPDF()
    {
        return $this->attributes['pdf'];
    }

    public function setPDF($pdf)
    {
        $this->attributes['pdf'] = $pdf;
    }

    public function getApprovedBy()
    {
        return $this->attributes['approved_by'];
    }

    public function setApprovedBy()
    {
        $this->attributes['approved_by'] = $approved_by;
    }
}

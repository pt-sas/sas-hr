<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class ImportAttendance extends Entity
{
    protected $trx_import_attendance_id;
    protected $documentno;
    protected $md_employee_id;
    protected $submissiondate;
    protected $submissiontype;
    protected $approveddate;
    protected $startdate;
    protected $enddate;
    protected $reason;
    protected $docstatus;
    protected $isapproved;
    protected $created_by;
    protected $updated_by;
    protected $sys_wfscenario_id;
    protected $isactive;

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function getImportAttendanceId()
    {
        return $this->attributes['trx_import_attendance_id'];
    }

    public function setImportAttendanceId($trx_import_attendance_id)
    {
        $this->attributes['trx_import_attendance_id'] = $trx_import_attendance_id;
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

    public function getSubmissionDate()
    {
        return $this->attributes['submissiondate'];
    }

    public function setSubmissionDate($submissiondate)
    {
        $this->attributes['submissiondate'] = $submissiondate;
    }

    public function getSubmissionType()
    {
        return $this->attributes['submissiontype'];
    }

    public function setSubmissionType($submissiontype)
    {
        $this->attributes['submissiontype'] = $submissiontype;
    }

    public function getApprovedDate()
    {
        return $this->attributes['approveddate'];
    }

    public function setApprovedDate($approveddate)
    {
        $this->attributes['approveddate'] = $approveddate;
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
        $this->attributes['isactive'] = $isactive;
    }

    public function getUpdatedAt()
    {
        return $this->attributes['updated_at'];
    }

    public function getCreatedAt()
    {
        return $this->attributes['created_at'];
    }
}

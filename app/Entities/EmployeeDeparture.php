<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class EmployeeDeparture extends Entity
{
    protected $trx_employee_departure_id;
    protected $md_employee_id;
    protected $documentno;
    protected $nik;
    protected $submissiondate;
    protected $submissiontype;
    protected $md_branch_id;
    protected $md_division_id;
    protected $md_position_id;
    protected $date;
    protected $departuretype;
    protected $departurerule;
    protected $description;
    protected $docstatus;
    protected $isapprove;
    protected $approveddate;
    protected $letterdate;
    protected $sys_wfscenario_id;
    protected $created_by;
    protected $updated_by;
    protected $fullname;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getEmployeeDepartureId()
    {
        return $this->attributes['trx_employee_departure_id'];
    }

    public function setEmployeeDepartureId($trx_employee_departure_id)
    {
        $this->attributes['trx_employee_departure_id'] = $trx_employee_departure_id;
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

    public function getNik()
    {
        return $this->attributes['nik'];
    }

    public function setNik($nik)
    {
        $this->attributes['nik'] = $nik;
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

    public function getPositionId()
    {
        return $this->attributes['md_position_id'];
    }

    public function setPositionId($md_position_id)
    {
        $this->attributes['md_position_id'] = $md_position_id;
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

    public function getDescription()
    {
        return $this->attributes['description'];
    }

    public function setDescription($description)
    {
        $this->attributes['description'] = $description;
    }

    public function getDepartureType()
    {
        return $this->attributes['departuretype'];
    }

    public function setDepartureType($departuretype)
    {
        $this->attributes['departuretype'] = $departuretype;
    }

    public function getDepartureRule()
    {
        return $this->attributes['departurerule'];
    }

    public function setDepartureRule($departurerule)
    {
        $this->attributes['departurerule'] = $departurerule;
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

    public function getLetterDate()
    {
        return $this->attributes['letterdate'];
    }

    public function setLetterDate($letterdate)
    {
        $this->attributes['letterdate'] = $letterdate;
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

    public function getFullName()
    {
        return $this->attributes['fullname'];
    }

    public function setFullName($fullname)
    {
        $this->attributes['fullname'] = $fullname;
    }
}

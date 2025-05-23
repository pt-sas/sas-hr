<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Absent extends Entity
{
    protected $trx_absent_id;
    protected $documentno;
    protected $md_employee_id;
    protected $nik;
    protected $md_branch_id;
    protected $md_division_id;
    protected $submissiondate;
    protected $receiveddate;
    protected $necessary;
    protected $submissiontype;
    protected $startdate;
    protected $enddate;
    protected $reason;
    protected $docstatus;
    protected $image;
    protected $isapproved;
    protected $approveddate;
    protected $sys_wfscenario_id;
    protected $isactive;
    protected $created_by;
    protected $updated_by;
    protected $md_leavetype_id;
    protected $image2;
    protected $image3;
    protected $enddate_realization;
    protected $isbranch;
    protected $branch_to;
    protected $reference_id;
    protected $totaldays;
    protected $img_medical;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

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

    public function getNecessary()
    {
        return $this->attributes['necessary'];
    }

    public function setNecessary($necessary)
    {
        $this->attributes['necessary'] = $necessary;
    }

    public function getSubmissionType()
    {
        return $this->attributes['submissiontype'];
    }

    public function setSubmissionType($submissiontype)
    {
        $this->attributes['submissiontype'] = $submissiontype;
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

    public function getLeaveTypeId()
    {
        return $this->attributes['md_leavetype_id'];
    }

    public function setLeaveTypeId($md_leavetype_id)
    {
        $this->attributes['md_leavetype_id'] = $md_leavetype_id;
    }

    public function getImage2()
    {
        return $this->attributes['image2'];
    }

    public function setImage2($image2)
    {
        $this->attributes['image2'] = $image2;
    }

    public function getImage3()
    {
        return $this->attributes['image3'];
    }

    public function setImage3($image3)
    {
        $this->attributes['image3'] = $image3;
    }

    public function getEndDateRealization()
    {
        return $this->attributes['enddate_realization'];
    }

    public function setEndDateRealization($enddate_realization)
    {

        $this->attributes['enddate_realization'] = $enddate_realization;
    }

    public function getBranchTo()
    {
        return $this->attributes['branch_to'];
    }

    public function setBranchTo($branch_to)
    {
        $this->attributes['branch_to'] = $branch_to;
    }

    public function getIsBranch()
    {
        return $this->attributes['isbranch'];
    }

    public function setIsBranch($isbranch)
    {
        return $this->attributes['isbranch'] = $isbranch;
    }

    public function getReferenceId()
    {
        return $this->attributes['reference_id'];
    }

    public function setReferenceId($reference_id)
    {
        $this->attributes['reference_id'] = $reference_id;
    }

    public function getTotalDays()
    {
        return $this->attributes['totaldays'];
    }

    public function setTotalDays($totaldays)
    {
        $this->attributes['totaldays'] = $totaldays;
    }

    public function getImageMedical()
    {
        return $this->attributes['img_medical'];
    }

    public function setImageMedical($img_medical)
    {
        $this->attributes['img_medical'] = $img_medical;
    }
}
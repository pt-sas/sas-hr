<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Memo extends Entity
{
    protected $trx_hr_memo_id;
    protected $documentno;
    protected $md_employee_id;
    protected $nik;
    protected $md_branch_id;
    protected $md_division_id;
    protected $submissiondate;
    protected $memodate;
    protected $memotype;
    protected $memocriteria;
    protected $memocontent;
    protected $totaldays;
    protected $description;
    protected $docstatus;
    protected $isapproved;
    protected $approveddate;
    protected $sys_wfscenario_id;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getTrxMemoId()
    {
        return $this->attributes['trx_hr_memo_id'];
    }

    public function setTrxMemoId($trx_hr_memo_id)
    {
        $this->attributes['trx_hr_memo_id'] = $trx_hr_memo_id;
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

    public function setMemoDate($memodate)
    {
        $this->attributes['memodate'] = $memodate;
    }

    public function getMemoDate()
    {
        return $this->attributes['memodate'];
    }

    public function getMemoType()
    {
        return $this->attributes['memotype'];
    }

    public function setMemoType($memotype)
    {
        $this->attributes['memotype'] = $memotype;
    }

    public function getMemoCriteria()
    {
        return $this->attributes['memocriteria'];
    }

    public function setMemoCriteria($memocriteria)
    {
        $this->attributes['memocriteria'] = $memocriteria;
    }

    public function getMemoContent()
    {
        return $this->attributes['memocontent'];
    }

    public function setMemoContent($memocontent)
    {
        $this->attributes['memocontent'] = $memocontent;
    }

    public function getTotalDays()
    {
        return $this->attributes['totaldays'];
    }

    public function setTotalDays($totaldays)
    {
        $this->attributes['totaldays'] = $totaldays;
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
}

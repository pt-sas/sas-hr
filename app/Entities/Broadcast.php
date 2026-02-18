<?php

namespace App\Entities;

use CodeIgniter\Entity;

class Broadcast extends Entity
{
    protected $trx_broadcast_id;
    protected $title;
    protected $message;
    protected $attachment;
    protected $attachment2;
    protected $attachment3;
    protected $md_employee_id;
    protected $md_branch_id;
    protected $md_division_id;
    protected $effective_date;
    protected $is_sent;
    protected $sentmethod;
    protected $lastupdate;
    protected $isactive;
    protected $updated_by;
    protected $updated_at;
    protected $created_by;
    protected $created_at;

    public function getBroadcastId()
    {
        return $this->attributes['trx_broadcast_id'];
    }

    public function setBroadcastId($trx_broadcast_id)
    {
        $this->attributes['trx_broadcast_id'] = $trx_broadcast_id;
    }

    public function getTitle()
    {
        return $this->attributes['title'];
    }

    public function setTitle($title)
    {
        $this->attributes['title'] = $title;
    }

    public function getMessage()
    {
        return $this->attributes['message'];
    }

    public function setMessage($message)
    {
        $this->attributes['message'] = $message;
    }

    public function getAttachment()
    {
        return $this->attributes['attachment'];
    }

    public function setAttachment($attachment)
    {
        $this->attributes['attachment'] = $attachment;
    }

    public function getAttachment2()
    {
        return $this->attributes['attachment2'];
    }

    public function setAttachment2($attachment2)
    {
        $this->attributes['attachment2'] = $attachment2;
    }

    public function getAttachment3()
    {
        return $this->attributes['attachment3'];
    }

    public function setAttachment3($attachment3)
    {
        $this->attributes['attachment3'] = $attachment3;
    }

    public function getCreatedBy()
    {
        return $this->attributes['created_by'];
    }

    public function setCreatedBy($created_by)
    {
        $this->attributes['created_by'] = $created_by;
    }

    public function getMdEmployeeId()
    {
        return $this->attributes['md_employee_id'];
    }

    public function setMdEmployeeId($md_employee_id)
    {
        $this->attributes['md_employee_id'] = $md_employee_id;
    }

    public function getMdBranchId()
    {
        return $this->attributes['md_branch_id'];
    }

    public function setMdBranchId($md_branch_id)
    {
        $this->attributes['md_branch_id'] = $md_branch_id;
    }

    public function getMdDivisionId()
    {
        return $this->attributes['md_division_id'];
    }

    public function setMdDivisionId($md_division_id)
    {
        $this->attributes['md_division_id'] = $md_division_id;
    }

    public function getEffectiveDate()
    {
        if ($this->attributes['effective_date'] === '0000-00-00 00:00:00' || empty($this->attributes['effective_date'])) return null;

        return $this->attributes['effective_date'];
    }

    public function setEffectiveDate($effective_date)
    {
        $this->attributes['effective_date'] = $effective_date;
    }

    public function getIsSent()
    {
        return $this->attributes['is_sent'];
    }

    public function setIsSent($is_sent)
    {
        $this->attributes['is_sent'] = $is_sent;
        return $this;
    }

    public function getSentMethod()
    {
        return $this->attributes['sentmethod'];
    }

    public function setSentMethod($sentmethod)
    {
        $this->attributes['sentmethod'] = $sentmethod;
        return $this;
    }

    public function getLastUpdate()
    {
        if ($this->attributes['lastupdate'] === '0000-00-00 00:00:00' || empty($this->attributes['lastupdate'])) return null;
        
        return $this->attributes['lastupdate'];
    }

    public function setLastUpdate($lastupdate)
    {
        $this->attributes['lastupdate'] = $lastupdate;
        return $this;
    }

    public function getIsActive()
    {
        return $this->attributes['isactive'];
    }

    public function setIsActive($isactive)
    {
        $this->attributes['isactive'] = $isactive;
        return $this;
    }

    public function getUpdatedBy()
    {
        return $this->attributes['updated_by'];
    }

    public function setUpdatedBy($updated_by)
    {
        $this->attributes['updated_by'] = $updated_by;
        return $this;
    }

    public function getUpdatedAt()
    {
        return $this->attributes['updated_at'];
    }

    public function setUpdatedAt($updated_at)
    {
        $this->attributes['updated_at'] = $updated_at;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->attributes['created_at'];
    }

    public function setCreatedAt($created_at)
    {
        $this->attributes['created_at'] = $created_at;
        return $this;
    }


}
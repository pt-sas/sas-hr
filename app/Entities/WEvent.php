<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class WEvent extends Entity
{
    protected $sys_wfevent_audit_id;
    protected $sys_wfactivity_id;
    protected $sys_wfresponsible_id;
    protected $sys_user_id;
    protected $state;
    protected $isapproved;
    protected $oldvalue;
    protected $newvalue;
    protected $table;
    protected $record_id;
    protected $isactive;
    protected $created_by;
    protected $updated_by;
    protected $tableline;
    protected $recordline_id;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getWfEventAuditId()
    {
        return $this->attributes['sys_wfevent_audit_id'];
    }

    public function setWfEventAuditId($sys_wfevent_audit_id)
    {
        $this->attributes['sys_wfevent_audit_id'] = $sys_wfevent_audit_id;
    }

    public function getWfActivityId()
    {
        return $this->attributes['sys_wfactivity_id'];
    }

    public function setWfActivityId($sys_wfactivity_id)
    {
        $this->attributes['sys_wfactivity_id'] = $sys_wfactivity_id;
    }

    public function getWfResponsibleId()
    {
        return $this->attributes['sys_wfresponsible_id'];
    }

    public function setWfResponsibleId($sys_wfresponsible_id)
    {
        $this->attributes['sys_wfresponsible_id'] = $sys_wfresponsible_id;
    }

    public function getSysUserId()
    {
        return $this->attributes['sys_user_id'];
    }

    public function setSysUserId($sys_user_id)
    {
        $this->attributes['sys_user_id'] = $sys_user_id;
    }

    public function getState()
    {
        return $this->attributes['state'];
    }

    public function setState($state)
    {
        $this->attributes['state'] = $state;
    }

    public function getIsApproved()
    {
        return $this->attributes['isapproved'];
    }

    public function setIsApproved($isapproved)
    {
        if ($isapproved)
            $this->attributes['isapproved'] = 'Y';
        else
            $this->attributes['isapproved'] = 'N';
    }

    public function getOldValue()
    {
        return $this->attributes['oldvalue'];
    }

    public function setOldValue($oldvalue)
    {
        if ($oldvalue == null)
            $this->attributes['oldvalue'] = NULL;
        else
            $this->attributes['oldvalue'] = $oldvalue;
    }

    public function getNewValue()
    {
        return $this->attributes['newvalue'];
    }

    public function setNewValue($newvalue)
    {
        if ($newvalue == null)
            $this->attributes['newvalue'] = NULL;
        else
            $this->attributes['newvalue'] = $newvalue;
    }

    public function getTable()
    {
        return $this->attributes['table'];
    }

    public function setTable($table)
    {
        $this->attributes['table'] = $table;
    }

    public function getRecordId()
    {
        return $this->attributes['record_id'];
    }

    public function setRecordId($record_id)
    {
        $this->attributes['record_id'] = $record_id;
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

    public function getTableLine()
    {
        return $this->attributes['tableline'];
    }

    public function setTableLine($tableline)
    {
        if (is_null($tableline))
            $this->attributes['tableline'] = NULL;
        else
            $this->attributes['tableline'] = $tableline;
    }

    public function getRecordLineId()
    {
        return $this->attributes['recordline_id'];
    }

    public function setRecordLineId($recordline_id)
    {
        if (is_null($recordline_id))
            $this->attributes['recordline_id'] = NULL;
        else
            $this->attributes['recordline_id'] = $recordline_id;
    }
}

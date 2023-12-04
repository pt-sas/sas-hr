<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class ChangeLog extends Entity
{
    protected $sys_changelog_id;
    protected $sys_sessions_id;
    protected $isactive;
    protected $created_by;
    protected $updated_by;
    protected $table;
    protected $column;
    protected $record_id;
    protected $oldvalue;
    protected $newvalue;
    protected $description;
    protected $eventchangelog;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getChangeLogId()
    {
        return $this->attributes['sys_changelog_id'];
    }

    public function setChangeLogId($sys_changelog_id)
    {
        $this->attributes['sys_changelog_id'] = $sys_changelog_id;
    }

    public function getSessionId()
    {
        return $this->attributes['sys_sessions_id'];
    }

    public function setSessionId($sys_sessions_id)
    {
        $this->attributes['sys_sessions_id'] = $sys_sessions_id;
    }

    public function getTable()
    {
        return $this->attributes['table'];
    }

    public function setTable($table)
    {
        $this->attributes['table'] = $table;
    }

    public function getColumn()
    {
        return $this->attributes['column'];
    }

    public function setColumn($column)
    {
        $this->attributes['column'] = $column;
    }

    public function getRecordId()
    {
        return $this->attributes['record_id'];
    }

    public function setRecordId($record_id)
    {
        $this->attributes['record_id'] = $record_id;
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

    public function getDescription()
    {
        return $this->attributes['description'];
    }

    public function setDescription($description)
    {
        $this->attributes['description'] = $description;
    }
    public function getEventChangeLog()
    {
        return $this->attributes['eventchangelog'];
    }

    public function setEventChangeLog($eventchangelog)
    {
        $this->attributes['eventchangelog'] = $eventchangelog;
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

<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class WActivity extends Entity
{
    protected $sys_wfactivity_id;
    protected $sys_wfscenario_id;
    protected $sys_wfresponsible_id;
    protected $sys_user_id;
    protected $state;
    protected $processed;
    protected $textmsg;
    protected $table;
    protected $record_id;
    protected $menu;
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

    public function getWfActivityId()
    {
        return $this->attributes['sys_wfactivity_id'];
    }

    public function setWfActivityId($sys_wfactivity_id)
    {
        $this->attributes['sys_wfactivity_id'] = $sys_wfactivity_id;
    }

    public function getWfScenarioId()
    {
        return $this->attributes['sys_wfscenario_id'];
    }

    public function setWfScenarioId($sys_wfscenario_id)
    {
        $this->attributes['sys_wfscenario_id'] = $sys_wfscenario_id;
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

    public function getProcessed()
    {
        return $this->attributes['processed'];
    }

    public function setProcessed($processed)
    {
        if ($processed)
            $this->attributes['processed'] = 'Y';
        else
            $this->attributes['processed'] = 'N';
    }

    public function getTextMsg()
    {
        return $this->attributes['textmsg'];
    }

    public function setTextMsg($textmsg)
    {
        if ($textmsg == null)
            $this->attributes['textmsg'] = NULL;
        else
            $this->attributes['textmsg'] = $textmsg;
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

    public function getMenu()
    {
        return $this->attributes['menu'];
    }

    public function setMenu($menu)
    {
        $this->attributes['menu'] = $menu;
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

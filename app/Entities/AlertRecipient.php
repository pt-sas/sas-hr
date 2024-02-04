<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class AlertRecipient extends Entity
{
    protected $md_alertrecipient_id;
    protected $record_id;
    protected $sys_user_id;
    protected $sys_role_id;
    protected $created_by;
    protected $updated_by;
    protected $table;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getAlertRecipientId()
    {
        return $this->attributes['md_alertrecipient_id'];
    }

    public function setAlertRecipientId($md_alertrecipient_id)
    {
        $this->attributes['md_alertrecipient_id'] = $md_alertrecipient_id;
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

    public function getUserId()
    {
        return $this->attributes['sys_user_id'];
    }

    public function setUserId($sys_user_id)
    {
        $this->attributes['sys_user_id'] = $sys_user_id;
    }

    public function getRoleId()
    {
        return $this->attributes['sys_role_id'];
    }

    public function setRoleId($sys_role_id)
    {
        $this->attributes['sys_role_id'] = $sys_role_id;
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

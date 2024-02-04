<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class WScenarioDetail extends Entity
{
    protected $sys_wfscenario_detail_id;
    protected $grandtotal;
    protected $lineno;
    protected $sys_wfscenario_id;
    protected $sys_wfresponsible_id;
    protected $sys_notiftext_id;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getWfScenarioDetailId()
    {
        return $this->attributes['sys_wfscenario_detail_id'];
    }

    public function setWfScenarioDetailId($sys_wfscenario_detail_id)
    {
        $this->attributes['sys_wfscenario_detail_id'] = $sys_wfscenario_detail_id;
    }

    public function getGrandTotal()
    {
        return $this->attributes['grandtotal'];
    }

    public function setGrandTotal($grandtotal)
    {
        $this->attributes['grandtotal'] = $grandtotal;
    }

    public function getLineNo()
    {
        return $this->attributes['lineno'];
    }

    public function setLineNo($lineno)
    {
        $this->attributes['lineno'] = $lineno;
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

    public function getNotifTextId()
    {
        return $this->attributes['sys_notiftext_id'];
    }

    public function setNotifTextId($sys_notiftext_id)
    {
        $this->attributes['sys_notiftext_id'] = $sys_notiftext_id;
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

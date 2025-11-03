<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class DocumentType extends Entity
{
    protected $md_doctype_id;
    protected $name;
    protected $isrealization;
    protected $isapprovedline;
    protected $description;
    protected $isactive;
    protected $created_by;
    protected $updated_by;
    protected $leader_id;
    protected $is_realization_mgr;
    protected $days_realization_mgr;
    protected $is_realization_hrd;
    protected $days_realization_hrd;
    protected $auto_not_approve_days;
    protected $sys_submenu_id;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getDocTypeId()
    {
        return $this->attributes['md_doctype_id'];
    }

    public function setDocTypeId($md_doctype_id)
    {
        $this->attributes['md_doctype_id'] = $md_doctype_id;
    }

    public function getName()
    {
        return $this->attributes['name'];
    }

    public function setName($name)
    {
        $this->attributes['name'] = $name;
    }

    public function getIsRealization()
    {
        return $this->attributes['isrealization'];
    }

    public function setIsRealization($isrealization)
    {
        return $this->attributes['isrealization'] = $isrealization;
    }

    public function getIsApprovedLine()
    {
        return $this->attributes['isapprovedline'];
    }

    public function setIsApprovedLine($isapprovedline)
    {
        return $this->attributes['isapprovedline'] = $isapprovedline;
    }

    public function getDescription()
    {
        return $this->attributes['description'];
    }

    public function setDescription($description)
    {
        $this->attributes['description'] = $description;
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

    public function setCreatedAt($created_at)
    {
        $this->attributes['created_at'] = $created_at;
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

    public function setUpdatedAt($updated_at)
    {
        $this->attributes['updated_at'] = $updated_at;
    }

    public function getUpdatedBy()
    {
        return $this->attributes['updated_by'];
    }

    public function setUpdatedBy($updated_by)
    {
        $this->attributes['updated_by'] = $updated_by;
    }

    public function getIsRealizationMgr()
    {
        return $this->attributes['is_realization_mgr'];
    }

    public function setIsRealizationMgr($is_realization_mgr)
    {
        $this->attributes['is_realization_mgr'] = $is_realization_mgr;
    }

    public function getDaysRealizationMgr()
    {
        return $this->attributes['days_realization_mgr'];
    }

    public function setDaysRealizationMgr($days_realization_mgr)
    {
        $this->attributes['days_realization_mgr'] = $days_realization_mgr;
    }

    public function getIsRealizationHrd()
    {
        return $this->attributes['is_realization_hrd'];
    }

    public function setIsRealizationHrd($is_realization_hrd)
    {
        $this->attributes['is_realization_hrd'] = $is_realization_hrd;
    }

    public function getDaysRealizationHrd()
    {
        return $this->attributes['days_realization_hrd'];
    }

    public function setDaysRealizationHrd($days_realization_hrd)
    {
        $this->attributes['days_realization_hrd'] = $days_realization_hrd;
    }

    public function getAutoNotApproveDays()
    {
        return $this->attributes['auto_not_approve_days'];
    }

    public function setAutoNotApproveDays($auto_not_approve_days)
    {
        $this->attributes['auto_not_approve_days'] = $auto_not_approve_days;
    }

    public function getSubmenuId()
    {
        return $this->attributes['sys_submenu_id'];
    }

    public function setSubmenuId($sys_submenu_id)
    {
        $this->attributes['sys_submenu_id'] = $sys_submenu_id;
    }
}

<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class DocAction extends Entity
{
    protected $sys_docaction_id;
    protected $sys_role_id;
    protected $menu;
    protected $ref_list;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getDocActionId()
    {
        return $this->attributes['sys_docaction_id'];
    }

    public function setDocActionId($sys_docaction_id)
    {
        $this->attributes['sys_docaction_id'] = $sys_docaction_id;
    }

    public function getRoleId()
    {
        return $this->attributes['sys_role_id'];
    }

    public function setRoleId($sys_role_id)
    {
        $this->attributes['sys_role_id'] = $sys_role_id;
    }

    public function getMenu()
    {
        return $this->attributes['menu'];
    }

    public function setMenu($menu)
    {
        $this->attributes['menu'] = $menu;
    }

    public function getRefList()
    {
        return $this->attributes['ref_list'];
    }

    public function setRefList($ref_list)
    {
        $this->attributes['ref_list'] = $ref_list;
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

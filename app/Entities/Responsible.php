<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Responsible extends Entity
{
    protected $sys_wfresponsible_id;
    protected $name;
    protected $description;
    protected $responsibletype;
    protected $sys_role_id;
    protected $sys_user_id;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getWfResponsibleId()
    {
        return $this->attributes['sys_wfresponsible_id'];
    }

    public function setWfResponsibleId($sys_wfresponsible_id)
    {
        $this->attributes['sys_wfresponsible_id'] = $sys_wfresponsible_id;
    }

    public function getName()
    {
        return $this->attributes['name'];
    }

    public function setName($name)
    {
        $this->attributes['name'] = $name;
    }

    public function getDescription()
    {
        return $this->attributes['description'];
    }

    public function setDescription($description)
    {
        $this->attributes['description'] = $description;
    }

    public function getResponsibleType()
    {
        return $this->attributes['responsibletype'];
    }

    public function setResponsibleType($responsibletype)
    {
        $this->attributes['responsibletype'] = $responsibletype;
    }

    public function getRoleId()
    {
        return $this->attributes['sys_role_id'];
    }

    public function setRoleId($sys_role_id)
    {
        return $this->attributes['sys_role_id'] = $sys_role_id;
    }

    public function getUserId()
    {
        return $this->attributes['sys_user_id'];
    }

    public function setUserId($sys_user_id)
    {
        return $this->attributes['sys_user_id'] = $sys_user_id;
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

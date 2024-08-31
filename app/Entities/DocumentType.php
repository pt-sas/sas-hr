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
}

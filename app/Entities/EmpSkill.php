<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class EmpSkill extends Entity
{
    protected $md_employee_skills_id;
    protected $md_employee_id;
    protected $name;
    protected $skilltype;
    protected $ability;
    protected $written_ability;
    protected $verbal_ability;
    protected $description;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getEmpSkillsId()
    {
        return $this->attributes['md_employee_skills_id'];
    }

    public function setEmpSkillsId($md_employee_skills_id)
    {
        $this->attributes['md_employee_skills_id'] = $md_employee_skills_id;
    }

    public function getEmployeeId()
    {
        return $this->attributes['md_employee_id'];
    }

    public function setEmployeeId($md_employee_id)
    {
        $this->attributes['md_employee_id'] = $md_employee_id;
    }

    public function getName()
    {
        return $this->attributes['name'];
    }

    public function setName($name)
    {
        $this->attributes['name'] = $name;
    }

    public function getSkillType()
    {
        return $this->attributes['skilltype'];
    }

    public function setSkillType($skilltype)
    {
        $this->attributes['skilltype'] = $skilltype;
    }

    public function getAbility()
    {
        return $this->attributes['ability'];
    }

    public function setAbility($ability)
    {
        $this->attributes['ability'] = $ability;
    }

    public function getWrittenAbility()
    {
        return $this->attributes['written_ability'];
    }

    public function setWrittenAbility($written_ability)
    {
        $this->attributes['written_ability'] = $written_ability;
    }

    public function getVerbalAbility()
    {
        return $this->attributes['verbal_ability'];
    }

    public function setVerbalAbility($verbal_ability)
    {
        $this->attributes['verbal_ability'] = $verbal_ability;
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

<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class AssignmentDetail extends Entity
{
    protected $trx_assignment_detail_id;
    protected $trx_assignment_id;
    protected $md_employee_id;
    protected $nik;
    protected $description;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getAssignmentDetailId()
    {
        return $this->attributes['trx_assignment_detail_id'];
    }

    public function setAssignmentDetailId($trx_assignment_detail_id)
    {
        $this->attributes['trx_assignment_detail_id'] = $trx_assignment_detail_id;
    }

    public function getAssignmentId()
    {
        return $this->attributes['trx_assignment_id'];
    }

    public function setAssignmentId($trx_assignment_id)
    {
        $this->attributes['trx_assignment_id'] = $trx_assignment_id;
    }

    public function getEmployeeId()
    {
        return $this->attributes['md_employee_id'];
    }

    public function setEmployeeId($md_employee_id)
    {
        $this->attributes['md_employee_id'] = $md_employee_id;
    }

    public function getNik()
    {
        return $this->attributes['nik'];
    }

    public function setNik($nik)
    {
        $this->attributes['nik'] = $nik;
    }

    public function getDescription()
    {
        return $this->attributes['description'];
    }

    public function setDescription($description)
    {
        $this->attributes['description'] = $description;
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
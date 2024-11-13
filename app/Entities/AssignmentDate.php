<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class AssignmentDate extends Entity
{
    protected $trx_assignment_date_id;
    protected $trx_assignment_detail_id;
    protected $date;
    protected $isagree;
    protected $table;
    protected $reference_id;
    protected $comment;
    protected $description;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getAssignmentDateId()
    {
        return $this->attributes['trx_assignment_date_id'];
    }

    public function setAssignmentDateId($trx_assignment_date_id)
    {
        $this->attributes['trx_assignment_date_id'] = $trx_assignment_date_id;
    }

    public function getAssignmentDetailId()
    {
        return $this->attributes['trx_assignment_detail_id'];
    }

    public function setAssignmentDetailId($trx_assignment_detail_id)
    {
        $this->attributes['trx_assignment_detail_id'] = $trx_assignment_detail_id;
    }

    public function getDate()
    {
        return $this->attributes['date'];
    }

    public function setDate($date)
    {
        $this->attributes['date'] = $date;
    }

    // Getter and Setter for isagree
    public function getIsAgree()
    {
        return $this->attributes['isagree'];
    }

    public function setIsAgree($isagree)
    {
        $this->attributes['isagree'] = $isagree;
    }

    // Getter and Setter for reference_id
    public function getReferenceId()
    {
        return $this->attributes['reference_id'];
    }

    public function setReferenceId($reference_id)
    {
        $this->attributes['reference_id'] = $reference_id;
    }

    public function getDescription()
    {
        return $this->attributes['description'];
    }

    public function setDescription($description)
    {
        $this->attributes['description'] = $description;
    }

    public function getComment()
    {
        return $this->attributes['comment'];
    }

    public function setComment($comment)
    {
        $this->attributes['comment'] = $comment;
    }

    public function getTable()
    {
        return $this->attributes['table'];
    }

    public function setTable($table)
    {
        $this->attributes['table'] = $table;
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

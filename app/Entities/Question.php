<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Question extends Entity
{
    protected $md_question_id;
    protected $md_question_group_id;
    protected $no;
    protected $question;
    protected $answertype;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getQuestionId()
    {
        return $this->attributes['md_question_id'];
    }

    public function setQuestionId($md_question_id)
    {
        $this->attributes['md_question_id'] = $md_question_id;
    }

    public function getQuestionGroupId()
    {
        return $this->attributes['md_question_group_id'];
    }

    public function setQuestionGroupId($md_question_group_id)
    {
        $this->attributes['md_question_group_id'] = $md_question_group_id;
    }

    public function getNo()
    {
        return $this->attributes['no'];
    }

    public function setNo($no)
    {
        $this->attributes['no'] = $no;
    }

    public function getQuestion()
    {
        return $this->attributes['question'];
    }

    public function setQuestion($description)
    {
        $this->attributes['description'] = $description;
    }

    public function getAnswerType()
    {
        return $this->attributes['answertype'];
    }

    public function setAnswerType($answertype)
    {
        $this->attributes['answertype'] = $answertype;
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

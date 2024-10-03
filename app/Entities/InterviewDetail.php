<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class InterviewDetail extends Entity
{
    protected $trx_interview_detail_id;
    protected $trx_interview_id;
    protected $md_question_group_id;
    protected $no;
    protected $md_question_id;
    protected $answertype;
    protected $answer;
    protected $description;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getInterviewDetailId()
    {
        return $this->attributes['trx_interview_detail_id'];
    }

    public function setInterviewDetailId($trx_interview_detail_id)
    {
        $this->attributes['trx_interview_detail_id'] = $trx_interview_detail_id;
    }

    public function getInterviewId()
    {
        return $this->attributes['trx_interview_id'];
    }

    public function setInterviewId($trx_interview_id)
    {
        $this->attributes['trx_interview_id'] = $trx_interview_id;
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

    public function getQuestionId()
    {
        return $this->attributes['md_question_id'];
    }

    public function setQuestionId($md_question_id)
    {
        $this->attributes['md_question_id'] = $md_question_id;
    }

    public function getAnswerType()
    {
        return $this->attributes['answertype'];
    }

    public function setAnswerType($answertype)
    {
        $this->attributes['answertype'] = $answertype;
    }

    public function getAnswer()
    {
        return $this->attributes['answer'];
    }

    public function setAnswer($answer)
    {
        $this->attributes['answer'] = $answer;
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

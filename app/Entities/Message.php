<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Message extends Entity
{
    protected $trx_message_id;
    protected $author_id;
    protected $subject;
    protected $body;
    protected $messagedate;
    protected $recipient_id;
    protected $isread;
    protected $isfavorite;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getMessageId()
    {
        return $this->attributes['trx_message_id'];
    }

    public function setMessageId($trx_message_id)
    {
        $this->attributes['trx_message_id'] = $trx_message_id;
    }

    public function getAuthorId()
    {
        return $this->attributes['author_id'];
    }

    public function setAuthorId($author_id)
    {
        $this->attributes['author_id'] = $author_id;
    }

    public function getSubject()
    {
        return $this->attributes['subject'];
    }

    public function setSubject($subject)
    {
        $this->attributes['subject'] = $subject;
    }

    public function getBody()
    {
        return $this->attributes['body'];
    }

    public function setBody($body)
    {
        $this->attributes['body'] = $body;
    }

    public function getMessageDate()
    {
        return $this->attributes['messagedate'];
    }

    public function setMessageDate($messagedate)
    {
        $this->attributes['messagedate'] = $messagedate;
    }

    public function getRecipientId()
    {
        return $this->attributes['recipient_id'];
    }

    public function setRecipientId($recipient_id)
    {
        $this->attributes['recipient_id'] = $recipient_id;
    }

    public function getIsRead()
    {
        return $this->attributes['isread'];
    }

    public function setIsRead($isread)
    {
        return $this->attributes['isread'] = $isread;
    }

    public function getIsFavorite()
    {
        return $this->attributes['isfavorite'];
    }

    public function setIsFavorite($isfavorite)
    {
        return $this->attributes['isfavorite'] = $isfavorite;
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

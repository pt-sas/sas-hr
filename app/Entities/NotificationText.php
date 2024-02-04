<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class NotificationText extends Entity
{
    protected $sys_notiftext_id;
    protected $name;
    protected $subject;
    protected $text;
    protected $text2;
    protected $text3;
    protected $notiftype;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getNotifTextId()
    {
        return $this->attributes['sys_notiftext_id'];
    }

    public function setNotifTextId($sys_notiftext_id)
    {
        $this->attributes['sys_notiftext_id'] = $sys_notiftext_id;
    }

    public function getName()
    {
        return $this->attributes['name'];
    }

    public function setName($name)
    {
        $this->attributes['name'] = $name;
    }

    public function getSubject()
    {
        return $this->attributes['subject'];
    }

    public function setSubject($subject)
    {
        $this->attributes['subject'] = $subject;
    }

    public function getText()
    {
        return $this->attributes['text'];
    }

    public function setText($text)
    {
        $this->attributes['text'] = $text;
    }

    public function getText1()
    {
        return $this->attributes['text1'];
    }

    public function setText1($text1)
    {
        $this->attributes['text1'] = $text1;
    }

    public function getText2()
    {
        return $this->attributes['text2'];
    }

    public function setText2($text2)
    {
        $this->attributes['text2'] = $text2;
    }

    public function getNotifType()
    {
        return $this->attributes['notiftype'];
    }

    public function setNotifType($notiftype)
    {
        $this->attributes['notiftype'] = $notiftype;
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

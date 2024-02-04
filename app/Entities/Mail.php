<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Mail extends Entity
{
    protected $protocol;
    protected $smtphost;
    protected $smtpport;
    protected $smtpcrypto;
    protected $smtpuser;
    protected $smtppassword;
    protected $requestemail;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getProtocol()
    {
        return $this->attributes['protocol'];
    }

    public function setProtocol($protocol)
    {
        $this->attributes['protocol'] = $protocol;
    }

    public function getSmtpHost()
    {
        return $this->attributes['smtphost'];
    }

    public function setSmtpHost($smtphost)
    {
        $this->attributes['smtphost'] = $smtphost;
    }

    public function getSmtpPort()
    {
        return $this->attributes['smtpport'];
    }

    public function setSmtpPort($smtpport)
    {
        $this->attributes['smtpport'] = $smtpport;
    }

    public function getSmtpCrypto()
    {
        return $this->attributes['smtpcrypto'];
    }

    public function setSmtpCrypto($smtpcrypto)
    {
        $this->attributes['smtpcrypto'] = $smtpcrypto;
    }

    public function getSmtpUser()
    {
        return $this->attributes['smtpuser'];
    }

    public function setSmtpUser($smtpuser)
    {
        $this->attributes['smtpuser'] = $smtpuser;
    }

    public function getSmtpPassword()
    {
        return $this->attributes['smtppassword'];
    }

    public function setSmtpPassword($smtppassword)
    {
        $this->attributes['smtppassword'] = $smtppassword;
    }

    public function getRequestEmail()
    {
        return $this->attributes['requestemail'];
    }

    public function setRequestEmail($requestemail)
    {
        $this->attributes['requestemail'] = $requestemail;
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

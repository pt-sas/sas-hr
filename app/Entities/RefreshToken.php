<?php

namespace App\Entities;

use CodeIgniter\Entity;

class RefreshToken extends Entity
{
    protected $sys_refresh_token_id;
    protected $sys_user_id;
    protected $user_agent;
    protected $token;
    protected $expired_date;
    protected $isrevoked;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
    ];

    public function getRefreshTokenId()
    {
        return $this->attributes['sys_refresh_token_id'];
    }

    public function setRefreshTokenId($sys_refresh_token_id)
    {
        $this->attributes['sys_refresh_token_id'] = $sys_refresh_token_id;
    }

    public function getUserId()
    {
        return $this->attributes['sys_user_id'];
    }

    public function setUserId($sys_user_id)
    {
        $this->attributes['sys_user_id'] = $sys_user_id;
    }

    public function getUserAgent()
    {
        return $this->attributes['user_agent'];
    }

    public function setUserAgent($user_agent)
    {
        $this->attributes['user_agent'] = $user_agent;
    }

    public function getToken()
    {
        return $this->attributes['token'];
    }

    public function setToken($token)
    {
        $this->attributes['token'] = $token;
    }

    public function getExpiredDate()
    {
        return $this->attributes['expired_date'];
    }

    public function setExpiredDate($expired_date)
    {
        $this->attributes['expired_date'] = $expired_date;
    }

    public function getIsRevoked()
    {
        return $this->attributes['isrevoked'];
    }

    public function setIsRevoked($isrevoked)
    {
        return $this->attributes['isrevoked'] = $isrevoked;
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

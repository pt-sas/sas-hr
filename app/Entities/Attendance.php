<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Attendance extends Entity
{
    protected $trx_attendance_id;
    protected $nik;
    protected $checktime;
    protected $created_by;
    protected $updated_by;
    protected $serialnumber;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getAttendanceId()
    {
        return $this->attributes['trx_attendance_id'];
    }

    public function setAttendanceId($trx_attendance_id)
    {
        $this->attributes['trx_attendance_id'] = $trx_attendance_id;
    }

    public function getNik()
    {
        return $this->attributes['nik'];
    }

    public function setNik($nik)
    {
        $this->attributes['nik'] = $nik;
    }

    public function getCheckTime()
    {
        return $this->attributes['checktime'];
    }

    public function setCheckTime($checktime)
    {
        $this->attributes['checktime'] = $checktime;
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

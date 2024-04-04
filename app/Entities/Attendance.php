<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Attendance extends Entity
{
    protected $trx_attendance_id;
    protected $nik;
    protected $date;
    protected $clock_in;
    protected $clock_out;
    protected $absent;
    protected $created_by;
    protected $updated_by;
    protected $description;

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

    public function getDate()
    {
        return $this->attributes['date'];
    }

    public function setDate($date)
    {
        $this->attributes['date'] = $date;
    }

    public function getClockIn()
    {
        return $this->attributes['clock_in'];
    }

    public function setClockIn($clock_in)
    {
        $this->attributes['clock_in'] = $clock_in;
    }

    public function getClockOut()
    {
        return $this->attributes['clock_out'];
    }

    public function setClockOut($clock_out)
    {
        $this->attributes['clock_out'] = $clock_out;
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

    public function getDescription()
    {
        return $this->attributes['description'];
    }

    public function setDescription($description)
    {
        $this->attributes['description'] = $description;
    }
}

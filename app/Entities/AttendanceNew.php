<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class AttendanceNew extends Entity
{
    protected $nik;
    protected $date;
    protected $clock_in;
    protected $clock_out;

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
}

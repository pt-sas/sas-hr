<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class EmpLicense extends Entity
{
    protected $md_employee_license_id;
    protected $md_employee_id;
    protected $licensetype;
    protected $license_id;
    protected $expireddate;
    protected $isactive;
    protected $created_by;
    protected $updated_by;

    protected $dates   = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function getEmpLicenseId()
    {
        return $this->attributes['md_employee_license_id'];
    }

    public function setEmpLicenseId($md_employee_license_id)
    {
        $this->attributes['md_employee_license_id'] = $md_employee_license_id;
    }

    public function getEmployeeId()
    {
        return $this->attributes['md_employee_id'];
    }

    public function setEmployeeId($md_employee_id)
    {
        $this->attributes['md_employee_id'] = $md_employee_id;
    }

    public function getLicenseType()
    {
        return $this->attributes['licensetype'];
    }

    public function setLicenseType($licensetype)
    {
        $this->attributes['licensetype'] = $licensetype;
    }

    public function getLicenseNo()
    {
        return $this->attributes['license_id'];
    }

    public function setLicenseNo($license_id)
    {
        $this->attributes['license_id'] = $license_id;
    }

    public function getExpiredDate()
    {
        if (!empty($this->attributes['expireddate']))
            return format_dmy($this->attributes['expireddate'], "-");

        return $this->attributes['expireddate'];
    }

    public function setExpiredDate($expireddate)
    {
        $this->attributes['expireddate'] = $expireddate;
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

<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Models\M_EmpWorkDay;
use App\Services\BaseServices;

class EmpWorkDayServices extends BaseServices
{
    public function __construct(int $userID)
    {
        parent::__construct();

        $this->userID = $userID;
        $this->model = new M_EmpWorkDay($this->request);
        $this->entity = new \App\Entities\EmpWorkDay();
    }

    public function getEmpWorkDay(int $md_employee_id, $startdate, $enddate)
    {
        $workDay = $this->model->where([
            'md_employee_id'    => $md_employee_id,
            'validfrom <='      => $startdate,
            'validto >='        => $enddate
        ])->orderBy('validfrom', 'ASC')->first();

        if (empty($workDay))
            throw new NotFoundException("Hari kerja karyawan belum ditentukan");

        return $workDay;
    }
}

<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Models\M_EmpWorkDay;
use App\Services\BaseServices;

class EmpWorkDayServices extends BaseServices
{
    public function __construct(int $userID, int $employeeID)
    {
        parent::__construct();

        //* Set User & Employee Session
        $this->userID = $userID;
        $this->employeeID = $employeeID;

        $this->model = new M_EmpWorkDay($this->request);
        $this->entity = new \App\Entities\EmpWorkDay();
    }

    public function getEmpWorkDay(int $md_employee_id, $startDate, $endDate)
    {
        $workDay = $this->model->where([
            'md_employee_id'    => $md_employee_id,
            'validfrom <='      => $startDate,
            'validto >='        => $endDate
        ])->orderBy('validfrom', 'ASC')->first();

        if (empty($workDay))
            throw new NotFoundException("Hari kerja karyawan belum ditentukan");

        return $workDay;
    }
}

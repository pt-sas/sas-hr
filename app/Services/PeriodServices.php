<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\M_Period;
use App\Models\M_PeriodControl;
use App\Models\M_Year;
use App\Services\BaseServices;

class PeriodServices extends BaseServices
{
    public function __construct(int $userID)
    {
        parent::__construct();

        $this->userID = $userID;
        $this->model = new M_Year($this->request);
        $this->modelDetail = new M_Period($this->request);
        $this->modelSubDetail = new M_PeriodControl($this->request);
        $this->entity = new \App\Entities\Year();
    }

    public function validatePeriod(int $submissionType, $startDate, $endDate, $holidays = [], $daysOff = [])
    {
        $dateRange = getDatesFromRange($startDate, $endDate, $holidays, 'Y-m-d', 'all', $daysOff);

        foreach ($dateRange as $date) {
            $period = $this->model->getPeriodStatus($date, $submissionType)->getRow();

            if (empty($period)) {
                throw new NotFoundException("Periode belum dibuat");
            } else if ($period->period_status == $this->PERIOD_CLOSED) {
                throw new ValidationException("Periode {$period->name} ditutup");
            }
        }
    }
}

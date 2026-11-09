<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Models\M_Bundling;
use App\Models\M_Datatable;
use App\Models\M_DocumentType;
use App\Services\PeriodServices;

class RealizationBundlingServices extends BaseServices
{
    protected $baseSubType;

    public function __construct(int $userID, int $employeeID)
    {
        parent::__construct();

        $this->userID = $userID;
        $this->employeeID = $employeeID;

        $this->model = new M_Bundling($this->request);
        $this->entity = new \App\Entities\Bundling();
        $this->baseSubType = $this->model->Pengajuan_Paket;
    }

    public function realizationList(array $params)
    {
        $today = date('Y-m-d');

        $dateRange = $params['daterange'] ?? null;
    }
}

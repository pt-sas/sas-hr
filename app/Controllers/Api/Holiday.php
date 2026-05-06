<?php

namespace App\Controllers\API;

use App\Controllers\ApiController;
use App\Models\M_Holiday;

class Holiday extends ApiController
{
    public function getHoliday()
    {
        $status_code = null;
        try {
            $mHoliday = new M_Holiday($this->request);

            $response = apiResponse(true, "Success", $mHoliday->getHolidayDate());
        } catch (\Exception $e) {

            log_message('error', 'Holiday [getHoliday] Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());

            $response = apiResponse(false, 'Internal Server Error');
            $status_code = 500;
        }

        return $this->respond($response, $status_code);
    }
}

<?php

namespace App\Controllers\API;

use App\Controllers\ApiController;
use App\Services\AttendanceServices;

class Attendance extends ApiController
{
    public function getTodayAttendance()
    {
        $status_code = null;

        try {
            $services = new AttendanceServices($this->jwt->sys_user_id, $this->jwt->md_employee_id);
            $result = $services->getTodayAttendance($this->jwt->md_employee_id);

            $response = apiResponse(true, "Success", $result);
        } catch (\App\Exceptions\BaseException $e) {
            $response = apiResponse(false, $e->getMessage());
            $status_code = $e->getStatusCode();
        } catch (\Exception $e) {
            log_message('error', 'Attendance [getTodayAttendance] Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());

            $response = apiResponse(false, 'Internal Server Error');
            $status_code = 500;
        }

        return $this->respond($response, $status_code);
    }
}

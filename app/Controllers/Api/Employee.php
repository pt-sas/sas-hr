<?php

namespace App\Controllers\API;

use App\Controllers\ApiController;
use App\Exceptions\ValidationException;
use App\Services\EmployeeServices;

class Employee extends ApiController
{
    public function getDetail()
    {
        $status_code = null;

        try {
            $service = new EmployeeServices($this->jwt->sys_user_id, $this->jwt->md_employee_id);

            //* Settle up parameter
            $md_employee_id = $this->request->getGet('md_employee_id');

            //* Validation parameter
            if (empty($md_employee_id))
                throw new ValidationException("Mohon mengisi karyawan");

            $data = $service->getEmployeeDetail($md_employee_id);

            $response = apiResponse(true, "Success", $data);
        } catch (\App\Exceptions\BaseException $e) {
            $response = apiResponse(false, $e->getMessage());
            $status_code = $e->getStatusCode();
        } catch (\Exception $e) {
            log_message('error', 'Employee [getDetail] Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());

            $response = apiResponse(false, 'Internal Server Error');
            $status_code = 500;
        }

        return $this->respond($response, $status_code);
    }
}

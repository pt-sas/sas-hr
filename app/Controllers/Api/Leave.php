<?php

namespace App\Controllers\API;

use App\Controllers\ApiController;
use App\Exceptions\ValidationException;
use App\Services\LeaveServices;
use UnSupportedException;

class Leave extends ApiController
{
    //* Show all data
    public function index()
    {
        $status_code = null;
        try {
            $service = new LeaveServices($this->jwt->sys_user_id);

            //* Settle up parameter
            $params = [
                'page'      => (int) ($this->request->getGet('page') ?? 1),
                'limit'     => (int) ($this->request->getGet('limit') ?? 1),
                'docstatus' => $this->request->getGet('docstatus'),
                'search'    => $this->request->getGet('search')
            ];

            //* Hardguard
            if ($params['page'] < 1) $params['page'] = 1;
            if ($params['limit'] < 1 || $params['limit'] > 100) {
                $params['limit'] = 10;
            }

            $result = $service->getPaginated($params, $this->jwt->md_employee_id);

            $response = apiResponse(true, "success", $result['data'], [], $result['meta']);
        } catch (\Exception $e) {
            log_message('error', 'Leave [index] Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());

            $response = apiResponse(false, 'Internal Server Error');
            $status_code = 500;
        }

        return $this->respond($response, $status_code);
    }

    //* For create new data
    public function create()
    {
        $service = new LeaveServices($this->jwt->sys_user_id);
        $data = $this->request->getJSON(true);
        $status_code = null;

        try {
            if (empty($data))
                throw new UnSupportedException("Unsupported Media");

            if (!$this->validation->run($data, 'leave')) {
                $response = apiResponse(false, "", [], $this->validation->getErrors());
                $status_code = 422;
            } else {
                $service->create($data);
                $response = apiResponse(true, "Data berhasil disimpan");
            }
        } catch (\App\Exceptions\BaseException $e) {
            $response = apiResponse(false, $e->getMessage());
            $status_code = $e->getStatusCode();
        } catch (\Exception $e) {
            log_message('error', 'Leave [create] Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());

            $response = apiResponse(false, 'Internal Server Error');
            $status_code = 500;
        }

        return $this->respond($response, $status_code);
    }

    //* For get data
    public function show($id = null)
    {
        $service = new LeaveServices($this->jwt->sys_user_id);
        $status_code = null;

        try {
            $data = $service->show($id);
            $response = apiResponse(true, "Success", $data);
        } catch (\App\Exceptions\BaseException $e) {
            $response = apiResponse(false, $e->getMessage());
            $status_code = $e->getStatusCode();
        } catch (\Exception $e) {
            log_message('error', 'Leave [show] Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());

            $response = apiResponse(false, 'Internal Server Error');
            $status_code = 500;
        }

        return $this->respond($response, $status_code);
    }

    //* For Process Submission
    public function proccessSubmission()
    {
        $service = new LeaveServices($this->jwt->sys_user_id);
        $data = $this->request->getJSON(true);
        $status_code = null;

        try {
            if (empty($data))
                throw new UnSupportedException("Unsupported Media");

            //* checking data docaction
            if (empty($data['docaction']))
                throw new ValidationException("Silahkan pilih tindakan terlebih dahulu");

            $message = $service->proccessTransaction($data['id'], $data['docaction']);

            $response = apiResponse(true, $message);
        } catch (\App\Exceptions\BaseException $e) {
            $response = apiResponse(false, $e->getMessage());
            $status_code = $e->getStatusCode();
        } catch (\Exception $e) {
            log_message('error', 'Leave [ProcessSubmission] Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());

            $response = apiResponse(false, 'Internal Server Error');
            $status_code = 500;
        }

        return $this->respond($response, $status_code);
    }

    public function getAvailableLeaves()
    {
        $status_code = null;

        try {
            $service = new LeaveServices($this->jwt->sys_user_id);

            //* Settle up parameter
            $md_employee_id = $this->request->getGet('md_employee_id');
            $startDate = $this->request->getGet('startdate');

            //* Validation parameter
            if (empty($md_employee_id))
                throw new ValidationException("Mohon mengisi karyawan");

            if (empty($startDate))
                throw new ValidationException("Mohon mengisi tanggal mulai");

            $data = $service->getAvailableDays($md_employee_id, date('Y-m-d', strtotime($startDate)));

            $response = apiResponse(true, "Success", $data);
        } catch (\App\Exceptions\BaseException $e) {
            $response = apiResponse(false, $e->getMessage());
            $status_code = $e->getStatusCode();
        } catch (\Exception $e) {
            log_message('error', 'Leave [getAvailableLeaves] Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());

            $response = apiResponse(false, 'Internal Server Error');
            $status_code = 500;
        }

        return $this->respond($response, $status_code);
    }
}

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

            $response = apiResponse($result['status'], "success", $result['message']['data'], [], $result['message']['meta']);
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
                $response = $service->create($data);
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

    //* For preparing data to edited
    public function edit($id = null)
    {
        $service = new LeaveServices($this->jwt->sys_user_id);
        $status_code = null;

        try {
            $response = $service->getData($id);
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

            $response = $service->proccessTransaction($data['id'], $data['docaction']);
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
}

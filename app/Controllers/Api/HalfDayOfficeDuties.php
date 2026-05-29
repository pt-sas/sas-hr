<?php

namespace App\Controllers\API;

use App\Controllers\ApiController;
use App\Services\AuthServices;
use App\Services\HalfDayOfficeDutiesServices;

class HalfDayOfficeDuties extends ApiController
{
    protected $menuURL = 'tugas-kantor-fka';

    //* Show all data
    public function index()
    {
        $status_code = null;
        try {
            //* Check Access
            $authServices = new AuthServices($this->jwt->sys_user_id, $this->jwt->md_employee_id, $this->jwt->sys_role_id);
            $authServices->checkAccess($this->menuURL, $this->Method_VIEW);

            $service = new HalfDayOfficeDutiesServices($this->jwt->sys_user_id, $this->jwt->md_employee_id);

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
        } catch (\App\Exceptions\BaseException $e) {
            $response = apiResponse(false, $e->getMessage());
            $status_code = $e->getStatusCode();
        } catch (\Exception $e) {
            log_message('error', 'HalfDayOfficeDuties [index] Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());

            $response = apiResponse(false, 'Internal Server Error');
            $status_code = 500;
        }

        return $this->respond($response, $status_code);
    }

    //* For create new data
    public function create()
    {
        $service = new HalfDayOfficeDutiesServices($this->jwt->sys_user_id, $this->jwt->md_employee_id);
        $data = $this->request->getPost();
        $status_code = null;

        try {
            if (empty($data))
                throw new UnSupportedException("Unsupported Media");

            //* Check Access
            $authServices = new AuthServices($this->jwt->sys_user_id, $this->jwt->md_employee_id, $this->jwt->sys_role_id);
            empty($data['trx_absent_id']) ? $authServices->checkAccess($this->menuURL, $this->Method_CREATE) : $authServices->checkAccess($this->menuURL, $this->Method_UPDATE);

            if (!$this->validation->run($data, 'pengajuantugas')) {
                $response = apiResponse(false, "Validation Rules", [], $this->validation->getErrors());
                $status_code = 422;
            } else {
                $service->create($data);
                $response = apiResponse(true, "Data berhasil disimpan");
            }
        } catch (\App\Exceptions\BaseException $e) {
            $response = apiResponse(false, $e->getMessage());
            $status_code = $e->getStatusCode();
        } catch (\Exception $e) {
            log_message('error', 'HalfDayOfficeDuties [create] Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());

            $response = apiResponse(false, 'Internal Server Error');
            $status_code = 500;
        }

        return $this->respond($response, $status_code);
    }

    //* For get data
    public function show($id = null)
    {
        $service = new HalfDayOfficeDutiesServices($this->jwt->sys_user_id, $this->jwt->md_employee_id);
        $status_code = null;

        try {
            //* Check Access
            $authServices = new AuthServices($this->jwt->sys_user_id, $this->jwt->md_employee_id, $this->jwt->sys_role_id);
            $authServices->checkAccess($this->menuURL, $this->Method_VIEW);

            $data = $service->show($id);
            $response = apiResponse(true, "Success", $data);
        } catch (\App\Exceptions\BaseException $e) {
            $response = apiResponse(false, $e->getMessage());
            $status_code = $e->getStatusCode();
        } catch (\Exception $e) {
            log_message('error', 'HalfDayOfficeDuties [show] Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());

            $response = apiResponse(false, 'Internal Server Error');
            $status_code = 500;
        }

        return $this->respond($response, $status_code);
    }

    //* For Process Submission
    public function proccessSubmission()
    {
        $service = new HalfDayOfficeDutiesServices($this->jwt->sys_user_id, $this->jwt->md_employee_id);
        $data = $this->request->getJSON(true);
        $status_code = null;

        try {
            //* Check Access
            $authServices = new AuthServices($this->jwt->sys_user_id, $this->jwt->md_employee_id, $this->jwt->sys_role_id);
            $authServices->checkAccess($this->menuURL, $this->Method_UPDATE);

            if (empty($data))
                throw new UnSupportedException("Unsupported Media");

            //* checking data docaction
            if (empty($data['docaction']))
                throw new ValidationException("Silahkan pilih tindakan terlebih dahulu");

            $message = $service->proccessTransaction($data['id'], $data['docaction'], $data['subtype']);

            $response = apiResponse(true, $message);
        } catch (\App\Exceptions\BaseException $e) {
            $response = apiResponse(false, $e->getMessage());
            $status_code = $e->getStatusCode();
        } catch (\Exception $e) {
            log_message('error', 'HalfDayOfficeDuties [ProcessSubmission] Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());

            $response = apiResponse(false, 'Internal Server Error');
            $status_code = 500;
        }

        return $this->respond($response, $status_code);
    }
}

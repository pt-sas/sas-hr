<?php

namespace App\Controllers\API;

use App\Controllers\ApiController;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnSupportedException;
use App\Services\NotificationServices;
use Override;

class Notification extends ApiController
{
    //* Show all data
    public function index()
    {
        $status_code = null;
        try {
            $service = new NotificationServices($this->jwt->sys_user_id, $this->jwt->md_employee_id);

            //* Settle up parameter
            $params = [
                'page'      => (int) ($this->request->getGet('page') ?? 1),
                'limit'     => (int) ($this->request->getGet('limit') ?? 1),
                'search'    => $this->request->getGet('search')
            ];

            //* Hardguard
            if ($params['page'] < 1) $params['page'] = 1;
            if ($params['limit'] < 1 || $params['limit'] > 100) {
                $params['limit'] = 10;
            }

            $result = $service->getPaginated($params);

            $response = apiResponse(true, "success", $result['data'], [], $result['meta']);
        } catch (\App\Exceptions\BaseException $e) {
            $response = apiResponse(false, $e->getMessage());
            $status_code = $e->getStatusCode();
        } catch (\Exception $e) {
            log_message('error', 'Notification [index] Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());

            $response = apiResponse(false, 'Internal Server Error');
            $status_code = 500;
        }

        return $this->respond($response, $status_code);
    }

    public function updateRead(int $id)
    {
        $status_code = null;

        try {
            if (empty($id))
                throw new NotFoundException("ID Not Found");

            $service = new NotificationServices($this->jwt->sys_user_id, $this->jwt->md_employee_id);
            $service->updateRead($id);
            $response = apiResponse(true, "Data berhasil disimpan");
        } catch (\App\Exceptions\BaseException $e) {
            $response = apiResponse(false, $e->getMessage());
            $status_code = $e->getStatusCode();
        } catch (\Exception $e) {
            log_message('error', 'Notification [updateRead] Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());

            $response = apiResponse(false, 'Internal Server Error');
            $status_code = 500;
        }

        return $this->respond($response, $status_code);
    }

    public function delete($id = null)
    {
        $status_code = null;

        try {
            $service = new NotificationServices($this->jwt->sys_user_id, $this->jwt->md_employee_id);
            $service->destroy($id);

            $response = apiResponse(true, "Data berhasil dihapus");
        } catch (\App\Exceptions\BaseException $e) {
            $response = apiResponse(false, $e->getMessage());
            $status_code = $e->getStatusCode();
        } catch (\Exception $e) {
            log_message('error', 'Notification [delete] Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());

            $response = apiResponse(false, 'Internal Server Error');
            $status_code = 500;
        }

        return $this->respond($response, $status_code);
    }
}

<?php

namespace App\Controllers\API;

use App\Controllers\ApiController;
use App\Exceptions\ValidationException;
use App\Services\FCMServices;

class FCM extends ApiController
{
    public function registerToken()
    {
        $data = $this->request->getJSON(true);
        $status_code = null;

        try {
            if (empty($data))
                throw new UnSupportedException("Unsupported Media");

            if (!$this->validation->run($data, 'fcm')) {
                $response = apiResponse(false, "Validation Rules", [], $this->validation->getErrors());
                $status_code = 422;
            } else {
                logMessage('berhasil masuk');
                $service = new FCMServices($this->jwt->sys_user_id, $this->jwt->md_employee_id);
                $service->register($data);
                $response = apiResponse(true, "Data berhasil disimpan");
            }
        } catch (\App\Exceptions\BaseException $e) {
            $response = apiResponse(false, $e->getMessage());
            $status_code = $e->getStatusCode();
        } catch (\Exception $e) {
            log_message('error', 'FCM [registerToken] Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());

            $response = apiResponse(false, 'Internal Server Error');
            $status_code = 500;
        }

        return $this->respond($response, $status_code);
    }

    public function removeToken()
    {
        $data = $this->request->getJSON(true);
        $status_code = null;

        try {
            if (empty($data))
                throw new UnSupportedException("Unsupported Media");

            if (empty($data['device_token']))
                throw new ValidationException("Device token wajib disematkan");

            $service = new FCMServices($this->jwt->sys_user_id, $this->jwt->md_employee_id);
            $service->remove($data['device_token']);
            $response = apiResponse(true, "Data berhasil dihapus");
        } catch (\App\Exceptions\BaseException $e) {
            $response = apiResponse(false, $e->getMessage());
            $status_code = $e->getStatusCode();
        } catch (\Exception $e) {
            log_message('error', 'FCM [removeToken] Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());

            $response = apiResponse(false, 'Internal Server Error');
            $status_code = 500;
        }

        return $this->respond($response, $status_code);
    }

    public function sendTestMessage()
    {
        $data = $this->request->getJSON(true);
        $status_code = null;

        try {
            if (empty($data))
                throw new UnSupportedException("Unsupported Media");

            $service = new FCMServices($this->jwt->sys_user_id, $this->jwt->md_employee_id);
            $service->sendToToken($data['token'], $data['title'], $data['body']);
            $response = apiResponse(true, "Data berhasil dikirim");
        } catch (\App\Exceptions\BaseException $e) {
            $response = apiResponse(false, $e->getMessage());
            $status_code = $e->getStatusCode();
        } catch (\Exception $e) {
            log_message('error', 'FCM [sendMessage] Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());

            $response = apiResponse(false, 'Internal Server Error');
            $status_code = 500;
        }

        return $this->respond($response, $status_code);
    }
}

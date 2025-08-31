<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Configuration;
use App\Models\M_Employee;
use App\Models\M_User;
use Config\Services;


class Telegram extends BaseController
{
    public function __construct()
    {
        $this->request = Services::request();
    }

    public function telegramHook()
    {
        $input = $this->request->getJSON(true);
        $this->setUserID($input['message']['from']);

        // TODO : Need to send response to telegram so Telegram did'nt repeat sending data to HARMONY
        return $this->response->setJSON(['status' => 'ok']);
    }

    public function sendMessage($chat_id, $message)
    {
        $mConfig = new M_Configuration($this->request);
        $token = $mConfig->where('name', 'TOKEN_BOT_TELEGRAM')->first();
        $url = "https://api.telegram.org/bot{$token->value}/sendMessage";

        $data = [
            'chat_id' => $chat_id,
            'text'    => $message
        ];

        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $data
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    private function setUserID($data)
    {
        $mEmployee = new M_Employee($this->request);
        if (!isset($data['username'])) {
            log_message('warning', "Telegram username not found on message");
            return;
        }

        $employee = $mEmployee->where('telegram_username', $data['username'])->findAll();
        if (!$employee) {
            log_message('warning', "There is no employee had username {$data['username']}");
            return;
        }

        foreach ($employee as $emp) {
            if ((empty($emp->telegram_id) || $emp->telegram_id != $data['id'])) {
                $row = ['telegram_id' => $data['id']];
                $mEmployee->builder->update($row, [$mEmployee->primaryKey => $emp->md_employee_id]);
            }
        }
    }
}
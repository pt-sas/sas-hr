<?php

namespace App\Controllers\Backend;

use App\Controllers\BaseController;
use App\Models\M_Configuration;
use App\Models\M_Employee;
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

        if (isset($input['message'])) {
            $this->setUserID($input['message']['from']);
        }

        // TODO : Need to send response to telegram so Telegram did'nt repeat sending data to HARMONY
        return $this->response->setJSON(['status' => 'ok']);
    }

    public function sendMessage($chat_id, $message, $parse_mode = null)
    {
        $mConfig = new M_Configuration($this->request);
        $token = $mConfig->where('name', 'TOKEN_BOT_TELEGRAM')->first();
        $url = "https://api.telegram.org/bot{$token->value}/sendMessage";

        $data = [
            'chat_id' => $chat_id,
            'text'    => $message
        ];

        if ($parse_mode) {
            $data['parse_mode'] = $parse_mode;
        }

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

    public function sendPhoto($chat_id, $photo_path)
    {
        $mConfig = new M_Configuration($this->request);
        $token = $mConfig->where('name', 'TOKEN_BOT_TELEGRAM')->first();
        $url = "https://api.telegram.org/bot{$token->value}/sendPhoto";

        $data = [
            'chat_id' => $chat_id,
            'photo' => new \CURLFile($photo_path)
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $data
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public function sendDocument($chat_id, $document_path, $caption = '')
    {
        $mConfig = new M_Configuration($this->request);
        $token = $mConfig->where('name', 'TOKEN_BOT_TELEGRAM')->first();
        $url = "https://api.telegram.org/bot{$token->value}/sendDocument";

        $data = [
            'chat_id' => $chat_id,
            'document' => new \CURLFile($document_path)
        ];
        
        if (!empty($caption)) {
            $data['caption'] = $caption;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $data
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public function prepareHtmlForTelegram($html)
    {
        $html = preg_replace('/<\/p>\s*<p>/', "\n\n", $html);
        $html = preg_replace('/<p>/', '', $html);
        $html = preg_replace('/<\/p>/', "\n", $html);
        
        $html = preg_replace('/<div>/', "\n", $html);
        $html = preg_replace('/<\/div>/', '', $html);
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        
        $allowedTags = '<b><i><u><s><a><code><pre>';
        $html = strip_tags($html, $allowedTags);
        
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        $html = preg_replace("/\n{3,}/", "\n\n", $html);
        
        return trim($html);
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

                $message = "Halo {$emp->fullname}, Telegram ID sudah diset ke Harmony dengan id : {$data['id']}.";
                $this->sendMessage($data['id'], $message);
            }
        }
    }
}

<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Models\M_UserDevice;
use Config\Services;
use Firebase\JWT\JWT;

class FCMServices extends BaseServices
{
    protected  $serviceAccount;
    protected  $fcmEndpoint;
    protected  $projectId;

    public function __construct(int $userID, int $employeeID)
    {
        parent::__construct();

        //* Set User & Employee Session
        $this->userID = $userID;
        $this->employeeID = $employeeID;

        $this->model = new M_UserDevice($this->request);
        $this->entity = new \App\Entities\UserDevice();

        //* Decode Firebase Account JSON
        $this->serviceAccount = json_decode(file_get_contents(WRITEPATH . 'firebase/harmony-mobile-sas-firebase-adminsdk-fbsvc-86ef2d3eb8.json'), true);

        //* Set Project ID
        $this->projectId = $this->serviceAccount['project_id'];

        //* Set FCM End Point
        $this->fcmEndpoint = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
    }

    public function register($data)
    {
        $fcmToken = $data['fcm_token'];
        $platform = $data['platform'];
        $deviceToken = $data['device_token'];

        $currentData = $this->model->where(['sys_user_id' => $this->userID, 'device_token' => $deviceToken, 'platform' => $platform])->first();

        if ($currentData)
            $this->entity->sys_user_device_id = $currentData->sys_user_device_id;

        $this->entity->sys_user_id = $this->userID;
        $this->entity->fcm_token = $fcmToken;
        $this->entity->platform = $platform;
        $this->entity->device_token = $deviceToken;

        $this->save();
    }

    public function remove(String $deviceToken)
    {
        $currentData = $this->model->where(['sys_user_id' => $this->userID, 'device_token' => $deviceToken])->first();

        if (!$currentData) throw new NotFoundException("Device tidak ditemukan");

        $this->delete($currentData->sys_user_device_id);
    }

    public function sendToToken(string $token, string $title, string $body, array $data = [])
    {
        try {
            $accessToken = $this->getAccessToken();

            //* Preparing payload
            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body
                    ]
                ]
            ];

            if (!empty($data)) {
                foreach ($data as $key => $value) {
                    $data[$key] = (string)$value;
                }

                $payload['message']['data'] = $data;
            }

            $client = new \GuzzleHttp\Client();
            $client->post(
                $this->fcmEndpoint,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json'
                    ],
                    'json' => $payload
                ]
            );
        } catch (\Exception $e) {
            log_message('error', $e->getMessage());
            throw $e;
        }
    }

    private function getAccessToken()
    {
        $cachedToken = cache('google_fcm_token');

        //* Check cache on server
        if (!empty($cachedToken)) {
            return $cachedToken;
        }

        $iat = time();

        $jwt = JWT::encode(
            [
                "iss"    => $this->serviceAccount['client_email'],
                "scope"  => "https://www.googleapis.com/auth/firebase.messaging",
                "aud"    => $this->serviceAccount['token_uri'],
                "iat"    => $iat,
                'exp'    => $iat + 3600
            ],
            $this->serviceAccount['private_key'],
            'RS256'
        );

        $client = new \GuzzleHttp\Client();

        $response = $client->post($this->serviceAccount['token_uri'], [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt
            ]
        ]);

        $body = json_decode($response->getBody(), true);

        if (empty($body['access_token'])) {
            throw new \Exception(
                'Failed to get Google Access Token'
            );
        }

        cache()->save(
            'google_fcm_token',
            $body['access_token'],
            3500
        );

        return $body['access_token'];
    }
}

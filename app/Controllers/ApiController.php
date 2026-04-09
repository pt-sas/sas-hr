<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use Config\Services;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ApiController extends ResourceController
{
    protected $helpers = ['action_helper', 'api_helper', 'date_helper'];
    protected $validation;
    protected $request;
    protected $jwt;

    public function __construct()
    {
        $this->request = Services::request();
        $this->validation = Services::validation();
        $this->jwt = $this->decodeJWT();
    }

    private function decodeJWT()
    {
        $key = getenv('JWT_SECRET');
        $header = $this->request->getHeaderLine('Authorization');
        $token = null;

        if (!empty($header)) {
            if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {
                $token = $matches[1];
            }
        }

        return $token ? JWT::decode($token, new Key($key, 'HS256')) : null;
    }
}

<?php

namespace App\Controllers\API;

use App\Controllers\ApiController;
use App\Models\M_RefreshToken;
use App\Models\M_User;
use Firebase\JWT\JWT;

class Auth extends ApiController
{
    public function login()
    {
        $data = $this->request->getJSON(true);
        $status_code = null;

        try {
            if (!empty($data)) {
                if (!$this->validation->run($data, 'login')) {
                    $response = apiResponse(false, "", [], $this->validation->getErrors());
                    $status_code = 400;
                } else {
                    $model = new M_User($this->request);
                    $dataUser = $model->detail([
                        'BINARY(username)'    => $data['username']
                    ])->getRow();

                    if ($dataUser) {
                        if ($dataUser->isactive === "Y" && !empty($dataUser->role)) {
                            if (password_verify($data['password'], $dataUser->password)) {
                                $access_token = $this->generateAccessToken($dataUser->sys_user_id, $dataUser->md_employee_id);
                                $refresh_token = $this->generateRefreshToken($dataUser->sys_user_id, $this->request->getHeader('User-Agent'));

                                $apiData = ["token" => $access_token, "refresh_token" => $refresh_token];

                                $response = apiResponse(true, "Login Success", $apiData);
                            } else {
                                $response = apiResponse(false, "Wrong Username or Password");
                                $status_code = 401;
                            }
                        } else if ($dataUser->isactive !== "Y" || empty($dataUser->role)) {
                            $response = apiResponse(false, "User don't have access");
                            $status_code = 403;
                        }
                    } else {
                        $response = apiResponse(false, "Wrong Username or Password");
                        $status_code = 401;
                    }
                }
            } else {
                $response = apiResponse(false, "Unsupported Media");
                $status_code = 415;
            }
        } catch (\Exception $e) {
            log_message('error', '[login] Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());

            $response    = apiResponse(false, "Internal Server Error");
            $status_code = 500;
        }

        return $this->respond($response, $status_code);
    }

    public function refreshAccessToken()
    {
        $model = new M_RefreshToken();
        $mUser = new M_User($this->request);
        $data = $this->request->getJSON(true);
        $status_code = null;

        try {
            if (!empty($data)) {
                $ref_token = isset($data['refresh_token']) ? $data['refresh_token'] : null;

                if (!empty($ref_token)) {
                    $today = date('Y-m-d H:i:s');
                    $user_agent = $this->request->getHeader('User-Agent');
                    $hashed_ref_token = hash('sha256', $ref_token);

                    $ref_token_data = $model
                        ->where('user_agent', $user_agent)
                        ->where('token', $hashed_ref_token)
                        ->where('expired_date >=', $today)
                        ->where('isrevoked', 'N')
                        ->first();

                    if ($ref_token_data) {
                        $user = $mUser->where(['sys_user_id' => $ref_token_data->sys_user_id, 'isactive' => 'Y'])->first();

                        if ($user) {
                            $access_token = $this->generateAccessToken($user->sys_user_id, $user->md_employee_id);
                            $refresh_token = $this->generateRefreshToken($user->sys_user_id, $user_agent);
                            $model->revokeToken($hashed_ref_token);

                            $response = apiResponse(true, "Login Success", ['token' => $access_token, 'refresh_token' => $refresh_token]);
                        } else {
                            $response    = apiResponse(false, "User Not Exists");
                            $status_code = 400;
                        }
                    } else {
                        $response    = apiResponse(false, "Invalid Token");
                        $status_code = 401;
                    }
                } else {
                    $response    = apiResponse(false, "No Refresh Token");
                    $status_code = 400;
                }
            } else {
                $response    = apiResponse(false, "Unsupported Media");
                $status_code = 415;
            }
        } catch (\Exception $e) {
            log_message('error', '[refreshAccessToken] Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());

            $response    = apiResponse(false, "Internal Server Error");
            $status_code = 500;
        }

        return $this->respond($response, $status_code);
    }

    public function logout()
    {
        $model = new M_RefreshToken();
        $data = $this->request->getJSON(true);
        $status_code = null;

        try {
            if (!empty($data['refresh_token'])) {
                $model->revokeToken(hash('sha256', $data['refresh_token']));
                $response    = apiResponse(true, "Logout Success");
            } else {
                $response    = apiResponse(false, "Refresh token is required");
                $status_code = 400;
            }
        } catch (\Exception $e) {
            log_message('error', '[logout] Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine());

            $response    = apiResponse(false, "Internal Server Error");
            $status_code = 500;
        }

        return $this->respond($response, $status_code);
    }

    private function generateAccessToken($sys_user_id, $md_employee_id)
    {
        $key = getenv('JWT_SECRET');
        $access_ttl = getenv('JWT_ACCESS_TTL');
        $iat = time();

        $payload = [
            "iat" => $iat,
            "exp" => $iat + $access_ttl,
            "sys_user_id" => $sys_user_id,
            "md_employee_id" => $md_employee_id
        ];

        return JWT::encode($payload, $key);
    }

    private function generateRefreshToken($sys_user_id, $user_agent)
    {
        $model = new M_RefreshToken();
        $entity = new \App\Entities\RefreshToken;
        $token = bin2hex(random_bytes(64));

        $refresh_est = getenv('JWT_REFRESH_EST');
        $exp_date = date('Y-m-d H:i:s', strtotime("+{$refresh_est} days"));

        $entity->setUserId($sys_user_id);
        $entity->setToken(hash('sha256', $token));
        $entity->setUserAgent($user_agent);
        $entity->setExpiredDate($exp_date);
        $entity->setCreatedBy(100000);
        $entity->setUpdatedBy(100000);

        $model->save($entity);

        return $token;
    }
}

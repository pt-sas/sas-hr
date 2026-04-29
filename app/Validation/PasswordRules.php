<?php

namespace App\Validation;

use App\Models\M_User;

use Config\Services;

class PasswordRules
{
    public function match(string $value, string $params)
    {
        $params = explode(',', $params);
        $sys_user_id = trim($params[0]);

        $request = Services::request();

        $user = new M_User($request);
        $row = $user->find($sys_user_id);

        if (password_verify($value, $row->password))
            return true;

        return false;
    }
}

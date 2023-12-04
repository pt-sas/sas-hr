<?php

namespace App\Validation;

use App\Models\M_User;

use Config\Services;

class PasswordRules
{
    public function match()
    {
        $request = Services::request();

        $user = new M_User($request);
        $post = $request->getVar();

        $user_id = session()->get('sys_user_id');

        $row = $user->find($user_id);

        if (password_verify($post['password'], $row->password))
            return true;

        return false;
    }
}

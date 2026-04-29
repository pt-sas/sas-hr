<?php

namespace App\Services;

use App\Models\M_User;
use App\Services\BaseServices;

class UserServices extends BaseServices
{
    public function __construct(int $userID, int $employeeID)
    {
        parent::__construct();

        //* Set User & Employee Session
        $this->userID = $userID;
        $this->employeeID = $employeeID;

        $this->model = new M_User($this->request);
        $this->entity = new \App\Entities\User();
    }

    public function changePassword(String $password)
    {
        $this->entity->setUserId($this->userID);
        $this->entity->setPassword($password);

        $this->save();
    }
}

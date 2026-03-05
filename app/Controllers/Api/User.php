<?php

namespace App\Controllers\API;

use App\Controllers\ApiController;
use App\Models\M_User;

class User extends ApiController
{
    public function index()
    {
        $model = new M_User($this->request);

        return $this->respond(apiResponse(true, "Success", $model->findAll()));
    }

    public function show($id = null)
    {
        return parent::show($id);
    }
}

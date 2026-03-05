<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use Config\Services;

class ApiController extends ResourceController
{
    protected $helpers = ['action_helper'];
    protected $validation;

    public function __construct()
    {
        $this->validation = Services::validation();
    }
}

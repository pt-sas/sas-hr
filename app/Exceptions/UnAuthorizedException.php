<?php

namespace App\Exceptions;

use App\Exceptions\BaseException;

class UnAuthorizedException extends BaseException
{
    protected $statusCode = 401;
}

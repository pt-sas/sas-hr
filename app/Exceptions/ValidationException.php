<?php

namespace App\Exceptions;

use App\Exceptions\BaseException;

class ValidationException extends BaseException
{
    protected $statusCode = 422;
}

<?php

namespace App\Exceptions;

use App\Exceptions\BaseException;

class BusinessException extends BaseException
{
    protected $statusCode = 422;
}

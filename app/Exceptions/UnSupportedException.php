<?php

namespace App\Exceptions;

use App\Exceptions\BaseException;

class UnSupportedException extends BaseException
{
    protected $statusCode = 415;
}

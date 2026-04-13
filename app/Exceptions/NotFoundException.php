<?php

namespace App\Exceptions;

use App\Exceptions\BaseException;

class NotFoundException extends BaseException
{
    protected $statusCode = 404;
}

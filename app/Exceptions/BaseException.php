<?php

namespace App\Exceptions;

use Exception;

class BaseException extends Exception
{
    protected $statusCode = 400;

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}

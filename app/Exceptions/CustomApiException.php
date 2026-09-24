<?php

namespace App\Exceptions;

use Exception;

class CustomApiException extends Exception
{
    protected int $statusCode;

    protected array $errors;

    public function __construct($errors = [], $statusCode = 400, $message = 'Bad Request')
    {
        parent::__construct($message, $statusCode);
        $this->statusCode = $statusCode;
        $this->errors = $errors;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}

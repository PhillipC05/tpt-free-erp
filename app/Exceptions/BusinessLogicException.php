<?php

namespace App\Exceptions;

class BusinessLogicException extends ErpException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 422, $previous);
    }
}

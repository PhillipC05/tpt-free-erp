<?php

namespace App\Exceptions;

class ForbiddenException extends ErpException
{
    public function __construct(string $message = 'Insufficient permissions', ?\Throwable $previous = null)
    {
        parent::__construct($message, 403, $previous);
    }
}

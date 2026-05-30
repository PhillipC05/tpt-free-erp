<?php

namespace App\Exceptions;

class ResourceNotFoundException extends ErpException
{
    public function __construct(string $resource = 'Resource', ?\Throwable $previous = null)
    {
        parent::__construct("{$resource} not found", 404, $previous);
    }
}

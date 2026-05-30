<?php

namespace App\Exceptions;

use RuntimeException;

abstract class ErpException extends RuntimeException
{
    public function __construct(string $message = '', protected int $statusCode = 500, ?\Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function toArray(): array
    {
        return [
            'success' => false,
            'message' => $this->getMessage(),
        ];
    }
}

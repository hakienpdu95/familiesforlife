<?php

namespace App\Foundation\Exceptions;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class UnauthorizedException extends RuntimeException
{
    public function __construct(
        string $message = 'You are not authorized to perform this action.',
        private readonly int $statusCode = Response::HTTP_FORBIDDEN,
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}

<?php

namespace App\Services\AI\Exceptions;

use RuntimeException;

class UnsupportedAIProviderException extends RuntimeException
{
    public function __construct(string $provider)
    {
        parent::__construct("Unsupported AI provider: {$provider}");
    }
}

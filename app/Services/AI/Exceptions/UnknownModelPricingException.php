<?php

namespace App\Services\AI\Exceptions;

use RuntimeException;

class UnknownModelPricingException extends RuntimeException
{
    public function __construct(string $provider, string $model)
    {
        parent::__construct("No pricing configured for {$provider}/{$model} in config/ai_pricing.php");
    }
}

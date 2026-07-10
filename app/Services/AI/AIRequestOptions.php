<?php

namespace App\Services\AI;

final class AIRequestOptions
{
    public function __construct(
        public readonly string $model,
        public readonly array $responseSchema,
        public readonly float $temperature = 0.3,
        public readonly int $maxTokens = 2048,
        public readonly int $timeoutSeconds = 55,
    ) {}
}

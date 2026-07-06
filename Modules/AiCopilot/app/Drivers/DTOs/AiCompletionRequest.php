<?php

namespace Modules\AiCopilot\Drivers\DTOs;

readonly class AiCompletionRequest
{
    public function __construct(
        public string $model,
        public string $systemPrompt,
        public string $userMessage,
        public float  $temperature  = 0.7,
        public int    $maxTokens    = 1024,
        public int    $timeoutSec   = 30,
    ) {}
}

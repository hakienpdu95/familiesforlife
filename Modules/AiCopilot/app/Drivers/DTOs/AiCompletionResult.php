<?php

namespace Modules\AiCopilot\Drivers\DTOs;

readonly class AiCompletionResult
{
    public function __construct(
        public string $content,
        public string $finishReason,
        public int    $inputTokens,
        public int    $outputTokens,
        public int    $totalTokens,
        public float  $costUsd,
        public int    $durationMs,
    ) {}
}

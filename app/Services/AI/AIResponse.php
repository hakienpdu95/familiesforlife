<?php

namespace App\Services\AI;

final class AIResponse
{
    public function __construct(
        public readonly string $content,
        public readonly string $modelUsed,
        public readonly int $inputTokens,
        public readonly int $outputTokens,
        public readonly float $costUsd,
        public readonly array $raw,
        // Prompt caching (Phase 6, mục 8.7/15) — chỉ Anthropic điền giá trị khác 0, OpenAI luôn 0
        // vì caching của OpenAI tự động/không expose số token cache riêng qua route này.
        public readonly int $cacheCreationInputTokens = 0,
        public readonly int $cacheReadInputTokens = 0,
    ) {}
}

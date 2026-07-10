<?php

namespace App\Services\AI;

use App\Services\AI\Exceptions\UnknownModelPricingException;

final class CostCalculator
{
    /**
     * Phase 6 (mục 8.7/15) — cache_write/cache_read tính giá riêng (thường: write đắt hơn input
     * thường ~1.25x vì phải ghi cache, read rẻ hơn nhiều ~0.1x vì tái dùng). Model chưa cấu hình
     * giá cache riêng (VD OpenAI, hoặc model Anthropic cũ) → fallback về giá input thường, KHÔNG
     * throw — cache_write/cache_read luôn = 0 cho các model đó nên fallback không ảnh hưởng gì.
     */
    public static function calculate(
        string $provider,
        string $model,
        int $inputTokens,
        int $outputTokens,
        int $cacheWriteTokens = 0,
        int $cacheReadTokens = 0,
    ): float {
        $price = config("ai_pricing.{$provider}.{$model}")
            ?? throw new UnknownModelPricingException($provider, $model);

        $cost = ($inputTokens / 1_000_000 * $price['input'])
              + ($outputTokens / 1_000_000 * $price['output']);

        if ($cacheWriteTokens > 0) {
            $cost += $cacheWriteTokens / 1_000_000 * ($price['cache_write'] ?? $price['input']);
        }

        if ($cacheReadTokens > 0) {
            $cost += $cacheReadTokens / 1_000_000 * ($price['cache_read'] ?? $price['input']);
        }

        return $cost;
    }
}

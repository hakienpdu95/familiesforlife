<?php

namespace Modules\Aicem\Support;

/**
 * Suy ra 1 giá trị taxonomy price_tier (budget|mid|premium) từ giá thật của Product —
 * KHÔNG phải cột DB có sẵn, taxonomy() được phép tính toán (spec/AICEM_Technical_Specification.md
 * mục 6.2). Ngưỡng đọc từ config('aicem.price_tiers'), không hard-code.
 */
final class PriceTierBucketer
{
    public static function bucket(?float $price): string
    {
        if ($price === null) {
            return 'unknown';
        }

        $tiers = config('aicem.price_tiers', ['budget' => 300_000, 'mid' => 2_000_000]);

        if ($price <= $tiers['budget']) {
            return 'budget';
        }

        if ($price <= $tiers['mid']) {
            return 'mid';
        }

        return 'premium';
    }
}

<?php

namespace Modules\PensionCalculator\Features\PublicEstimation\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\PensionCalculator\Models\PensionParameterPeriod;
use Modules\PensionCalculator\Models\PensionPriceIndexCoefficient;
use Modules\PensionCalculator\Models\PensionRateBracket;

/**
 * spec/bhxh/PensionCalculator_Technical_Specification.md §10.2 — payload duy nhất của
 * `GET /api/pension-calculator/reference-data`. KHÔNG nhận input cá nhân, chỉ đọc dữ liệu
 * tham chiếu công khai; mọi phép tính (§6-§10) chạy phía client trên payload này.
 */
class BuildActiveParameterSetAction
{
    use AsAction;

    /** @return array{parameter_periods: array, price_index_coefficients: array, rate_brackets: array} */
    public function handle(): array
    {
        $periods = PensionParameterPeriod::with('supportTiers')
            ->orderBy('effective_from')
            ->get()
            ->map(fn (PensionParameterPeriod $period) => [
                'effective_from' => $period->effective_from->format('Y-m-d'),
                'rural_poverty_line' => (float) $period->rural_poverty_line,
                'reference_level' => (float) $period->reference_level,
                'contribution_rate_percent' => (float) $period->contribution_rate_percent,
                'ceiling_multiplier' => (int) $period->ceiling_multiplier,
                'support_tiers' => $period->supportTiers->map(fn ($tier) => [
                    'group_key' => $tier->group_key->value,
                    'support_percent' => (float) $tier->support_percent,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        $coefficients = PensionPriceIndexCoefficient::query()
            ->orderBy('settlement_year')
            ->orderBy('contribution_year')
            ->get()
            ->map(fn (PensionPriceIndexCoefficient $row) => [
                'settlement_year' => $row->settlement_year,
                'contribution_year' => $row->contribution_year,
                'coefficient' => (float) $row->coefficient,
            ])
            ->values()
            ->all();

        $rateBrackets = PensionRateBracket::query()
            ->orderBy('gender')
            ->orderBy('min_years_for_base_rate')
            ->get()
            ->map(fn (PensionRateBracket $row) => [
                'gender' => $row->gender,
                'min_years_for_base_rate' => $row->min_years_for_base_rate,
                'base_rate_percent' => (float) $row->base_rate_percent,
                'increment_percent_per_year' => (float) $row->increment_percent_per_year,
                'max_rate_percent' => (float) $row->max_rate_percent,
                'effective_from' => $row->effective_from->format('Y-m-d'),
            ])
            ->values()
            ->all();

        return [
            'parameter_periods' => $periods,
            'price_index_coefficients' => $coefficients,
            'rate_brackets' => $rateBrackets,
        ];
    }
}

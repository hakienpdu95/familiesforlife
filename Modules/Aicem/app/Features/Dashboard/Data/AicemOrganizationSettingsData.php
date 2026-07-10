<?php

namespace Modules\Aicem\Features\Dashboard\Data;

use Spatie\LaravelData\Data;

class AicemOrganizationSettingsData extends Data
{
    public function __construct(
        public readonly ?float $ai_monthly_budget_usd = null,
        public readonly ?string $ai_provider = null,
        public readonly ?string $ai_model = null,
        public readonly ?string $ai_api_key = null,
        public readonly ?int $rate_limit_per_minute = null,
        public readonly ?int $rate_limit_per_day = null,
    ) {}
}

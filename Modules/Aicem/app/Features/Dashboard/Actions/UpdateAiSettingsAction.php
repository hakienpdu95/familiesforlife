<?php

namespace Modules\Aicem\Features\Dashboard\Actions;

use App\Models\AiSetting;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Aicem\Features\Dashboard\Data\AiSettingsData;

/**
 * Cấu hình BYOK (provider/model/API key), hạn mức chi phí, và rate limit —
 * spec/AICEM_Technical_Specification.md mục 8.6/13. Quyền aicem.config.
 */
class UpdateAiSettingsAction
{
    use AsAction;

    public function handle(AiSettingsData $data): AiSetting
    {
        $settings = AiSetting::current();
        $providerConfig = null;

        if ($data->ai_provider) {
            $existingKey = $settings->provider_config['api_key'] ?? null;

            $providerConfig = [
                'provider' => $data->ai_provider,
                'model' => $data->ai_model,
                'api_key' => $data->ai_api_key !== null && $data->ai_api_key !== '' ? $data->ai_api_key : $existingKey,
            ];
        }

        $rateLimitOverride = array_filter([
            'per_minute' => $data->rate_limit_per_minute,
            'per_day' => $data->rate_limit_per_day,
        ], fn ($v) => $v !== null);

        $settings->fill([
            'monthly_budget_usd' => $data->ai_monthly_budget_usd,
            'provider_config' => $providerConfig,
            'rate_limit_override' => $rateLimitOverride ?: null,
        ])->save();

        return $settings;
    }
}

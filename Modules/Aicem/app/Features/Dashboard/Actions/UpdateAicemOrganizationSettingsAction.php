<?php

namespace Modules\Aicem\Features\Dashboard\Actions;

use App\Shared\Tenancy\Models\Organization;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Aicem\Features\Dashboard\Data\AicemOrganizationSettingsData;

/**
 * Cấu hình BYOK (provider/model/API key), hạn mức chi phí, và rate limit theo Organization —
 * spec/AICEM_Technical_Specification.md mục 8.6/13. Quyền aicem.config (System Admin).
 */
class UpdateAicemOrganizationSettingsAction
{
    use AsAction;

    public function handle(Organization $organization, AicemOrganizationSettingsData $data): Organization
    {
        $providerConfig = null;

        if ($data->ai_provider) {
            // Không ghi đè API key cũ nếu form để trống (không hiện lại giá trị cũ ra UI vì đã
            // encrypted — người dùng chỉ nhập khi muốn ĐỔI key, xem mục 13: không log/hiện API key).
            $existingKey = $organization->ai_provider_config['api_key'] ?? null;

            $providerConfig = [
                'provider' => $data->ai_provider,
                'model'    => $data->ai_model,
                'api_key'  => $data->ai_api_key !== null && $data->ai_api_key !== '' ? $data->ai_api_key : $existingKey,
            ];
        }

        $rateLimitOverride = array_filter([
            'per_minute' => $data->rate_limit_per_minute,
            'per_day'    => $data->rate_limit_per_day,
        ], fn ($v) => $v !== null);

        $organization->update([
            'ai_monthly_budget_usd'  => $data->ai_monthly_budget_usd,
            'ai_provider_config'     => $providerConfig,
            'ai_rate_limit_override' => $rateLimitOverride ?: null,
        ]);

        return $organization;
    }
}

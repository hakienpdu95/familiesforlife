<?php

namespace Modules\Aicem\Features\Dashboard\Http;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Aicem\Features\Dashboard\Actions\UpdateAicemOrganizationSettingsAction;
use Modules\Aicem\Features\Dashboard\Data\AicemOrganizationSettingsData;
use Modules\Aicem\Features\Dashboard\Queries\GetAicemUsageStatsHandler;
use Modules\Aicem\Features\Dashboard\Queries\GetAicemUsageStatsQuery;

class AicemDashboardController extends Controller
{
    public function overview(GetAicemUsageStatsHandler $handler): View
    {
        abort_unless(
            auth()->user()->hasAnyPermission(['aicem.view', 'aicem.use', 'aicem.config_prompt', 'aicem.config']),
            403
        );

        $stats = $handler->handle(new GetAicemUsageStatsQuery());

        return view('aicem::admin.dashboard.overview', ['stats' => $stats]);
    }

    public function settings(): View
    {
        $this->authorize('aicem.config');

        return view('aicem::admin.dashboard.settings', ['organization' => TenantContext::resolve()]);
    }

    public function updateSettings(Request $request, UpdateAicemOrganizationSettingsAction $action): RedirectResponse
    {
        $this->authorize('aicem.config');

        $validated = $request->validate([
            'ai_monthly_budget_usd' => ['nullable', 'numeric', 'min:0'],
            'ai_provider'           => ['nullable', Rule::in(['openai', 'anthropic'])],
            'ai_model'              => ['nullable', 'string', 'max:100'],
            'ai_api_key'            => ['nullable', 'string', 'max:500'],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:1'],
            'rate_limit_per_day'    => ['nullable', 'integer', 'min:1'],
        ]);

        $action->handle(TenantContext::resolve(), AicemOrganizationSettingsData::from($validated));

        return back()->with('success', 'Đã cập nhật cấu hình AICEM.');
    }
}

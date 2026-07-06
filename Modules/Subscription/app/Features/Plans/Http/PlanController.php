<?php

namespace Modules\Subscription\Features\Plans\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravelcm\Subscriptions\Models\Feature;
use Laravelcm\Subscriptions\Models\Plan;
use Modules\Subscription\Features\Plans\Actions\CreatePlanAction;
use Modules\Subscription\Features\Plans\Actions\SyncPlanFeaturesAction;
use Modules\Subscription\Features\Plans\Actions\TogglePlanAction;
use Modules\Subscription\Features\Plans\Actions\UpdatePlanAction;
use Modules\Subscription\Features\Plans\Data\PlanData;
use Modules\Subscription\Features\Plans\Data\PlanFeatureData;
use Modules\Subscription\Features\Plans\Queries\GetPlanHandler;
use Modules\Subscription\Features\Plans\Queries\GetPlanQuery;
use Modules\Subscription\Features\Plans\Queries\ListPlansHandler;
use Modules\Subscription\Features\Plans\Queries\ListPlansQuery;

class PlanController extends Controller
{
    public function __construct(
        private readonly ListPlansHandler $listHandler,
        private readonly GetPlanHandler   $getHandler,
    ) {}

    public function index()
    {
        $plans = $this->listHandler->handle(
            new ListPlansQuery(activeOnly: false, withFeatures: true)
        );

        return view('subscription::admin.plans.index', compact('plans'));
    }

    public function create()
    {
        $featureSlugs = $this->featureSlugs();

        return view('subscription::admin.plans.create', compact('featureSlugs'));
    }

    public function store(Request $request, CreatePlanAction $action): RedirectResponse
    {
        $validated = $this->validatePlan($request);
        $data      = PlanData::from($validated);
        $plan      = $action->handle($data);

        return redirect()->route('subscription.admin.plans.index')
            ->with('success', "Plan \"{$plan->name}\" đã được tạo.");
    }

    public function edit(Plan $plan)
    {
        $plan         = $this->getHandler->handle(new GetPlanQuery($plan->id, withFeatures: true));
        $featureSlugs = $this->featureSlugs();

        return view('subscription::admin.plans.edit', compact('plan', 'featureSlugs'));
    }

    public function update(Request $request, Plan $plan, UpdatePlanAction $action): RedirectResponse
    {
        $validated = $this->validatePlan($request, $plan->id);
        $data      = PlanData::from($validated);
        $action->handle($plan, $data);

        return redirect()->route('subscription.admin.plans.index')
            ->with('success', 'Cập nhật plan thành công.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->planSubscriptions()->findActive()->exists()) {
            return back()->withErrors(['plan' => 'Không thể xóa plan đang có subscription active.']);
        }

        $plan->delete();

        return redirect()->route('subscription.admin.plans.index')
            ->with('success', "Đã xóa plan \"{$plan->name}\".");
    }

    public function toggle(Plan $plan, TogglePlanAction $action): RedirectResponse
    {
        $action->handle($plan);

        return back()->with('success', $plan->is_active ? 'Plan đã bị vô hiệu hóa.' : 'Plan đã được kích hoạt.');
    }

    public function syncFeatures(Request $request, Plan $plan, SyncPlanFeaturesAction $action): RedirectResponse
    {
        $rows     = $request->input('features', []);
        $features = array_map(fn ($row) => PlanFeatureData::from($row), $rows);

        $action->handle($plan, $features);

        return back()->with('success', 'Cập nhật features thành công.');
    }

    private function validatePlan(Request $request, ?int $ignorePlanId = null): array
    {
        $slugRule = 'required|string|max:64|regex:/^[a-z0-9\-]+$/|unique:plans,slug' . ($ignorePlanId ? ",{$ignorePlanId}" : '');

        return $request->validate([
            'slug'             => $slugRule,
            'name'             => 'required|string|max:191',
            'description'      => 'nullable|string|max:1000',
            'price'            => 'required|numeric|min:0',
            'annual_price'     => 'nullable|numeric|min:0',
            'currency'         => 'required|string|max:10',
            'invoice_interval' => 'required|in:day,week,month,year',
            'invoice_period'   => 'required|integer|min:1',
            'trial_period'     => 'required|integer|min:0',
            'trial_interval'   => 'required|in:day,week,month,year',
            'grace_period'     => 'required|integer|min:0',
            'grace_interval'   => 'required|in:day,week,month,year',
            'is_active'        => 'boolean',
            'is_public'        => 'boolean',
            'tier'             => 'required|in:starter,growth,scale,enterprise',
            'tag_line'         => 'nullable|string|max:120',
            'badge_color'      => 'nullable|string|max:64',
        ]);
    }

    private function featureSlugs(): array
    {
        return array_merge(
            array_keys(config('subscription.module_features', [])),
            array_map(fn ($k) => str_replace('module.', '', $k),
                array_keys(config('subscription.module_features', []))),
            ['limit.employees', 'limit.members', 'limit.workflows', 'limit.projects', 'limit.storage_gb'],
            ['flag.api_access', 'flag.audit_log', 'flag.advanced_reports', 'flag.sso', 'flag.white_label', 'flag.custom_domain'],
            ['quota.ai_requests', 'quota.workflow_runs', 'quota.email_notifications'],
        );
    }
}

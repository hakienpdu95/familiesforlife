<?php

namespace Modules\Subscription\Features\AdminSubscriptions\Http;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravelcm\Subscriptions\Models\Plan;
use Modules\Subscription\Features\FeatureGate\Support\SubscriptionContext;
use Modules\Subscription\Features\Subscribe\Actions\SubscribeOrganizationAction;
use Modules\Subscription\Features\Subscribe\Data\SubscribeData;
use Modules\Subscription\Models\OrganizationFeatureOverride;

class AdminSubscriptionController extends Controller
{
    /** Danh sách tất cả feature slugs để dùng trong override modal. */
    private static array $featureNames = [
        'module.task'               => 'Module: Công việc',
        'module.sop'                => 'Module: SOP',
        'module.hr'                 => 'Module: Nhân sự',
        'module.crm'                => 'Module: CRM / Lead',
        'module.workflow'           => 'Module: Workflow',
        'module.ai'                 => 'Module: AI',
        'module.recruitment'        => 'Module: Tuyển dụng',
        'module.assessment'         => 'Module: Assessment',
        'module.project'            => 'Module: Dự án',
        'module.kc'                 => 'Module: Kho tri thức',
        'module.marketplace'        => 'Module: Marketplace',
        'limit.employees'           => 'Giới hạn: Nhân viên',
        'limit.members'             => 'Giới hạn: Người dùng',
        'limit.workflows'           => 'Giới hạn: Workflow',
        'limit.projects'            => 'Giới hạn: Dự án',
        'limit.storage_gb'          => 'Giới hạn: Dung lượng (GB)',
        'flag.api_access'           => 'Flag: API Access',
        'flag.audit_log'            => 'Flag: Audit Log',
        'flag.advanced_reports'     => 'Flag: Báo cáo nâng cao',
        'flag.sso'                  => 'Flag: SSO',
        'flag.white_label'          => 'Flag: White Label',
        'flag.custom_domain'        => 'Flag: Custom Domain',
        'quota.ai_requests'         => 'Quota: AI requests / tháng',
        'quota.workflow_runs'       => 'Quota: Workflow runs / tháng',
        'quota.email_notifications' => 'Quota: Email notifications / tháng',
    ];

    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $planId = $request->input('plan_id');
        $status = $request->input('status');

        $orgs = Organization::query()
            ->with(['planSubscriptions' => fn ($q) => $q->with('plan')->latest('starts_at')->limit(1)])
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when($planId, fn ($q) => $q->whereHas('planSubscriptions', fn ($s) => $s->where('plan_id', $planId)))
            ->when($status === 'no_plan', fn ($q) => $q->whereDoesntHave('planSubscriptions'))
            ->when($status === 'active',  fn ($q) => $q->whereHas('planSubscriptions', fn ($s) =>
                $s->whereNull('canceled_at')->where(fn ($x) => $x->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ))
            ->when($status === 'trial',   fn ($q) => $q->whereHas('planSubscriptions', fn ($s) =>
                $s->whereNotNull('trial_ends_at')->where('trial_ends_at', '>', now())
            ))
            ->when($status === 'expired', fn ($q) => $q->whereHas('planSubscriptions', fn ($s) =>
                $s->where('ends_at', '<=', now())
            ))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $plans        = Plan::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'slug', 'tier', 'price']);
        $featureNames = self::$featureNames;

        return view('subscription::admin.subscriptions.index', compact(
            'orgs', 'plans', 'featureNames', 'search', 'planId', 'status'
        ));
    }

    /**
     * Gán / thay plan mới cho tổ chức.
     * Nếu end_date được cung cấp, override ends_at sau khi tạo subscription.
     */
    public function assign(
        Request $request,
        Organization $organization,
        SubscribeOrganizationAction $action,
    ): RedirectResponse {
        $validated = $request->validate([
            'plan_id'    => 'required|exists:plans,id',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
            'reason'     => 'nullable|string|max:255',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);

        $subscription = $action->handle($organization, new SubscribeData(
            planId:    $plan->id,
            startDate: $validated['start_date'] ? new Carbon($validated['start_date']) : null,
        ));

        if (!empty($validated['end_date'])) {
            $subscription->update(['ends_at' => $validated['end_date']]);
        }

        SubscriptionContext::flush($organization->id);

        return back()->with('success', "Đã gán plan \"{$plan->name}\" cho \"{$organization->name}\".");
    }

    /**
     * Cập nhật ngày hết hạn (gia hạn) của subscription hiện tại, không đổi plan.
     */
    public function extend(Request $request, Organization $organization): RedirectResponse
    {
        $validated = $request->validate([
            'ends_at' => 'required|date',
            'reason'  => 'nullable|string|max:255',
        ]);

        $sub = $organization->planSubscription('main');
        if (!$sub) {
            return back()->withErrors(['subscription' => 'Tổ chức này chưa có subscription. Vui lòng gán plan trước.']);
        }

        $sub->update(['ends_at' => $validated['ends_at']]);
        SubscriptionContext::flush($organization->id);

        $until = Carbon::parse($validated['ends_at'])->format('d/m/Y');

        return back()->with('success', "Đã gia hạn subscription của \"{$organization->name}\" đến {$until}.");
    }

    /**
     * Override một feature cụ thể cho tổ chức, bỏ qua giới hạn từ plan.
     */
    public function override(Request $request, Organization $organization): RedirectResponse
    {
        $validated = $request->validate([
            'feature_slug'    => 'required|string|max:128',
            'value'           => 'required|string|max:255',
            'override_reason' => 'nullable|string|max:255',
            'expires_at'      => 'nullable|date',
        ]);

        OrganizationFeatureOverride::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'feature_slug'    => $validated['feature_slug'],
            ],
            [
                'value'           => $validated['value'],
                'override_reason' => $validated['override_reason'] ?? null,
                'expires_at'      => $validated['expires_at']      ?? null,
                'created_by'      => $request->user()->id,
            ]
        );

        SubscriptionContext::flush($organization->id);

        return back()->with('success', "Override \"{$validated['feature_slug']}\" cho \"{$organization->name}\" đã được lưu.");
    }
}

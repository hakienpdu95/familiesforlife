<?php

namespace Modules\Subscription\Features\Portal\Http;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravelcm\Subscriptions\Models\Plan;
use Modules\Subscription\Features\Portal\Queries\GetBillingDashboardHandler;
use Modules\Subscription\Features\Portal\Queries\GetBillingDashboardQuery;

class BillingPortalController extends Controller
{
    public function __construct(
        private readonly GetBillingDashboardHandler $dashboardHandler,
    ) {}

    public function billing(Request $request)
    {
        $orgId = $request->user()->current_organization_id;
        abort_unless($orgId, 403, 'No organization context.');
        $data  = $this->dashboardHandler->handle(new GetBillingDashboardQuery($orgId));

        return view('subscription::portal.billing', $data);
    }

    public function plans(Request $request)
    {
        $orgId = $request->user()->current_organization_id;
        abort_unless($orgId, 403, 'No organization context.');
        $data  = $this->dashboardHandler->handle(new GetBillingDashboardQuery($orgId));

        $plans = Plan::where('is_active', true)
            ->where('is_public', true)
            ->with('features')
            ->orderBy('sort_order')
            ->get();

        return view('subscription::portal.plans', array_merge($data, ['plans' => $plans]));
    }
}

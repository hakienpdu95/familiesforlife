<?php

namespace Modules\Subscription\Features\ChangePlan\Http;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Subscription\Exceptions\SubscriptionException;
use Modules\Subscription\Features\ChangePlan\Actions\DowngradePlanAction;
use Modules\Subscription\Features\ChangePlan\Actions\UpgradePlanAction;
use Modules\Subscription\Features\ChangePlan\Data\ChangePlanData;

class ChangePlanController extends Controller
{
    public function upgrade(Request $request): RedirectResponse
    {
        $request->validate(['plan_id' => 'required|integer|exists:plans,id']);

        $org  = $request->user()->organization;
        $data = new ChangePlanData(newPlanId: (int) $request->plan_id, reason: $request->reason);

        try {
            UpgradePlanAction::run($org, $data);
            return redirect()->route('subscription.portal.billing')
                ->with('success', 'Nâng cấp plan thành công.');
        } catch (SubscriptionException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function downgrade(Request $request): RedirectResponse
    {
        $request->validate(['plan_id' => 'required|integer|exists:plans,id']);

        $org  = $request->user()->organization;
        $data = new ChangePlanData(newPlanId: (int) $request->plan_id, reason: $request->reason);

        try {
            DowngradePlanAction::run($org, $data);
            return redirect()->route('subscription.portal.billing')
                ->with('success', 'Hạ cấp plan thành công.');
        } catch (SubscriptionException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

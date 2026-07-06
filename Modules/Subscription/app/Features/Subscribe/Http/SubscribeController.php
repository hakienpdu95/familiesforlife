<?php

namespace Modules\Subscription\Features\Subscribe\Http;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Subscription\Features\Subscribe\Actions\SubscribeOrganizationAction;
use Modules\Subscription\Features\Subscribe\Data\SubscribeData;

class SubscribeController extends Controller
{
    public function subscribe(Request $request): RedirectResponse
    {
        $request->validate(['plan_id' => 'required|integer|exists:plans,id']);

        $org  = $request->user()->organization;
        $data = new SubscribeData(planId: (int) $request->plan_id);

        SubscribeOrganizationAction::run($org, $data);

        return redirect()->route('subscription.portal.billing')
            ->with('success', 'Đăng ký plan thành công.');
    }
}

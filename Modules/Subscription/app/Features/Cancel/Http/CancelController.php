<?php

namespace Modules\Subscription\Features\Cancel\Http;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Subscription\Exceptions\SubscriptionException;
use Modules\Subscription\Features\Cancel\Actions\CancelSubscriptionAction;
use Modules\Subscription\Features\Cancel\Actions\ResumeSubscriptionAction;

class CancelController extends Controller
{
    public function cancel(Request $request): RedirectResponse
    {
        $org = $request->user()->organization;

        try {
            CancelSubscriptionAction::run($org, $request->input('reason', ''));
            return redirect()->route('subscription.portal.billing')
                ->with('success', 'Đã hủy subscription. Bạn vẫn có thể dùng đến hết kỳ thanh toán.');
        } catch (SubscriptionException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function resume(Request $request): RedirectResponse
    {
        $org = $request->user()->organization;

        try {
            ResumeSubscriptionAction::run($org);
            return redirect()->route('subscription.portal.billing')
                ->with('success', 'Đã khôi phục subscription thành công.');
        } catch (SubscriptionException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

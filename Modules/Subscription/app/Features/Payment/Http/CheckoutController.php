<?php

namespace Modules\Subscription\Features\Payment\Http;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravelcm\Subscriptions\Models\Plan;
use Modules\Subscription\Enums\GatewayType;
use Modules\Subscription\Exceptions\SubscriptionException;
use Modules\Subscription\Features\Payment\Actions\InitiatePaymentAction;
use Modules\Subscription\Features\Payment\Support\PaymentGatewayManager;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly InitiatePaymentAction $initiatePayment,
        private readonly PaymentGatewayManager $gateways,
    ) {}

    /** Show checkout page with gateway selection. */
    public function show(Request $request, Plan $plan)
    {
        $gateways = $this->gateways->enabled();
        return view('subscription::payment.checkout', compact('plan', 'gateways'));
    }

    /** Initiate payment after gateway is selected. */
    public function initiate(Request $request): RedirectResponse
    {
        $request->validate([
            'plan_id'    => 'required|integer|exists:plans,id',
            'gateway'    => 'required|string',
        ]);

        $org     = $request->user()->organization;
        $plan    = Plan::findOrFail($request->plan_id);
        $gateway = $request->gateway;

        try {
            $result = $this->initiatePayment->handle($org, $plan, $gateway, $request);
        } catch (SubscriptionException|\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return match ($result['gateway_type']) {
            GatewayType::Redirect => redirect($result['url']),
            GatewayType::Monitor  => redirect()->route('subscription.billing.transfer', $result['invoice']),
            GatewayType::Manual   => redirect()->route('subscription.billing.manual', $result['invoice']),
        };
    }
}

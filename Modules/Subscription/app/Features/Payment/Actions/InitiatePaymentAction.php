<?php

namespace Modules\Subscription\Features\Payment\Actions;

use App\Shared\Tenancy\Models\Organization;
use Illuminate\Http\Request;
use Laravelcm\Subscriptions\Models\Plan;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Subscription\Enums\GatewayType;
use Modules\Subscription\Enums\InvoiceType;
use Modules\Subscription\Enums\TransactionStatus;
use Modules\Subscription\Exceptions\SubscriptionException;
use Modules\Subscription\Features\Payment\Data\GenerateInvoiceData;
use Modules\Subscription\Features\Payment\Support\PaymentGatewayManager;
use Modules\Subscription\Models\PaymentTransaction;
use Modules\Subscription\Models\SubscriptionInvoice;

class InitiatePaymentAction
{
    use AsAction;

    public function __construct(
        private readonly GenerateInvoiceAction $generateInvoice,
        private readonly PaymentGatewayManager $gateways,
    ) {}

    /**
     * @return array{invoice: SubscriptionInvoice, url: ?string, gateway_type: GatewayType}
     */
    public function handle(Organization $org, Plan $plan, string $gatewaySlug, Request $request): array
    {
        $gateway = $this->gateways->resolve($gatewaySlug);

        if (!$gateway->isEnabled()) {
            throw new SubscriptionException("Cổng thanh toán [{$gatewaySlug}] chưa được cấu hình.");
        }

        $currentSub = $org->planSubscription('main');

        // Determine invoice type
        $invoiceType = match (true) {
            $currentSub === null         => InvoiceType::New,
            $plan->price > ($currentSub->plan->price ?? 0) => InvoiceType::Upgrade,
            default                      => InvoiceType::Renewal,
        };

        $returnUrl = route('subscription.billing.return', $gatewaySlug);

        $invoice = $this->generateInvoice->handle($org, new GenerateInvoiceData(
            organizationId: $org->id,
            subscriptionId: $currentSub?->id ?? 0,
            planId:         $plan->id,
            amount:         (float) $plan->price,
            currency:       $plan->currency ?? 'VND',
            invoiceType:    $invoiceType,
            newPlanId:      $invoiceType === InvoiceType::Upgrade ? $plan->id : null,
            gateway:        $gatewaySlug,
            billingPeriodStart: now(),
            billingPeriodEnd:   $currentSub?->ends_at ?? now()->addMonth(),
            idempotentKey:  "checkout-{$org->id}-{$plan->id}-" . now()->format('Ymd'),
        ));

        $checkoutUrl = $gateway->buildCheckoutUrl($invoice, $returnUrl);

        // Log outbound payment initiation
        PaymentTransaction::create([
            'organization_id' => $org->id,
            'invoice_id'      => $invoice->id,
            'gateway'         => $gatewaySlug,
            'direction'       => 'outbound',
            'status'          => TransactionStatus::Pending,
            'amount'          => $invoice->amount,
            'ip_addr'         => $request->ip(),
        ]);

        return [
            'invoice'      => $invoice,
            'url'          => $checkoutUrl,
            'gateway_type' => $gateway->type(),
        ];
    }
}

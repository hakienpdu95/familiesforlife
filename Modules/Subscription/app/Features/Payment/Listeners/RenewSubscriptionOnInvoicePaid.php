<?php

namespace Modules\Subscription\Features\Payment\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Subscription\Enums\InvoiceType;
use Modules\Subscription\Features\ChangePlan\Actions\UpgradePlanAction;
use Modules\Subscription\Features\ChangePlan\Data\ChangePlanData;
use Modules\Subscription\Features\FeatureGate\Support\SubscriptionContext;
use Modules\Subscription\Features\Payment\Events\InvoicePaid;
use Modules\Subscription\Features\Subscribe\Actions\SubscribeOrganizationAction;
use Modules\Subscription\Features\Subscribe\Data\SubscribeData;
use Modules\Subscription\Features\Subscribe\Events\SubscriptionRenewed;

class RenewSubscriptionOnInvoicePaid
{
    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice;
        $org     = $invoice->organization()->withoutGlobalScopes()->find($invoice->organization_id);

        if (!$org) {
            Log::error("RenewSubscription: org not found for invoice {$invoice->invoice_number}");
            return;
        }

        try {
            match ($invoice->invoice_type) {
                InvoiceType::New     => $this->handleNewSubscription($org, $invoice),
                InvoiceType::Upgrade => $this->handleUpgrade($org, $invoice),
                InvoiceType::Renewal => $this->handleRenewal($org, $invoice),
            };

            SubscriptionContext::flush($org->id);
        } catch (\Throwable $e) {
            Log::error("RenewSubscription: failed for invoice {$invoice->invoice_number}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function handleNewSubscription($org, $invoice): void
    {
        SubscribeOrganizationAction::run($org, new SubscribeData(
            planId: $invoice->plan_id,
        ));
    }

    private function handleUpgrade($org, $invoice): void
    {
        // new_plan_id is the plan being switched to
        $targetPlanId = $invoice->new_plan_id ?? $invoice->plan_id;

        UpgradePlanAction::run($org, new ChangePlanData(
            newPlanId: $targetPlanId,
            reason:    'Paid via ' . $invoice->gateway,
        ));
    }

    private function handleRenewal($org, $invoice): void
    {
        $sub = $org->planSubscription('main');

        if (!$sub) {
            Log::warning("RenewSubscription: no active subscription for org {$org->id} on renewal");
            return;
        }

        $sub->renew();
        SubscriptionRenewed::dispatch($org, $sub->fresh());
    }
}

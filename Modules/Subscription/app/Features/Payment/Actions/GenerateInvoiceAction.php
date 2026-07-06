<?php

namespace Modules\Subscription\Features\Payment\Actions;

use App\Shared\Tenancy\Models\Organization;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Subscription\Enums\InvoiceStatus;
use Modules\Subscription\Features\Payment\Data\GenerateInvoiceData;
use Modules\Subscription\Features\Payment\Services\InvoiceNumberService;
use Modules\Subscription\Models\SubscriptionInvoice;

class GenerateInvoiceAction
{
    use AsAction;

    public function __construct(
        private readonly InvoiceNumberService $invoiceNumbers,
    ) {}

    public function handle(Organization $org, GenerateInvoiceData $data): SubscriptionInvoice
    {
        return DB::transaction(function () use ($org, $data): SubscriptionInvoice {
            // Idempotency check inside the transaction prevents two concurrent checkouts
            // from both missing the SELECT and creating duplicate invoices (TOCTOU).
            // The unique constraint on idempotent_key is the final DB-level guard.
            if ($data->idempotentKey) {
                $existing = SubscriptionInvoice::withoutTenant()
                    ->where('organization_id', $org->id)
                    ->where('idempotent_key', $data->idempotentKey)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            $number = $this->invoiceNumbers->generate($org);

            return SubscriptionInvoice::create([
                'organization_id'      => $org->id,
                'subscription_id'      => $data->subscriptionId,
                'plan_id'              => $data->planId,
                'new_plan_id'          => $data->newPlanId,
                'invoice_number'       => $number,
                'amount'               => $data->amount,
                'currency'             => $data->currency,
                'status'               => InvoiceStatus::Pending,
                'invoice_type'         => $data->invoiceType,
                'gateway'              => $data->gateway,
                'billing_period_start' => $data->billingPeriodStart,
                'billing_period_end'   => $data->billingPeriodEnd,
                'due_date'             => $data->dueDate ?? now()->addDays(3),
                'idempotent_key'       => $data->idempotentKey,
                'notes'                => $data->notes,
            ]);
        });
    }
}

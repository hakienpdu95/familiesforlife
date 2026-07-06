<?php

namespace Modules\Subscription\Features\Payment\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Subscription\Enums\InvoiceStatus;
use Modules\Subscription\Features\Payment\Events\InvoicePaid;
use Modules\Subscription\Models\SubscriptionInvoice;

class MarkInvoicePaidAction
{
    use AsAction;

    public function handle(
        SubscriptionInvoice $invoice,
        string $paymentRef,
        string $paymentMethod,
    ): SubscriptionInvoice {
        // Atomic conditional update — only one concurrent webhook call wins.
        // The WHERE status = Pending prevents duplicate processing when the same
        // IPN is delivered twice simultaneously (gateway retry race condition).
        $affected = 0;

        DB::transaction(function () use ($invoice, $paymentRef, $paymentMethod, &$affected): void {
            $affected = DB::table('subscription_invoices')
                ->where('id', $invoice->id)
                ->where('status', InvoiceStatus::Pending->value)
                ->update([
                    'status'         => InvoiceStatus::Paid->value,
                    'paid_at'        => now(),
                    'payment_ref'    => $paymentRef,
                    'payment_method' => $paymentMethod,
                    'updated_at'     => now(),
                ]);
        });

        $fresh = $invoice->fresh();

        // Only dispatch when THIS call performed the state transition
        if ($affected > 0) {
            InvoicePaid::dispatch($fresh);
        }

        return $fresh;
    }
}

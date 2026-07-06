<?php

namespace Modules\Subscription\Features\Payment\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Subscription\Enums\InvoiceStatus;
use Modules\Subscription\Models\SubscriptionInvoice;

class VoidInvoiceAction
{
    use AsAction;

    public function handle(SubscriptionInvoice $invoice, string $reason = ''): SubscriptionInvoice
    {
        if ($invoice->isPaid()) {
            throw new \RuntimeException('Không thể void invoice đã thanh toán.');
        }

        if ($invoice->status === InvoiceStatus::Void) {
            return $invoice;
        }

        DB::transaction(function () use ($invoice, $reason): void {
            DB::table('subscription_invoices')
                ->where('id', $invoice->id)
                ->whereIn('status', [InvoiceStatus::Pending->value])
                ->update([
                    'status'     => InvoiceStatus::Void->value,
                    'notes'      => $reason ?: $invoice->notes,
                    'updated_at' => now(),
                ]);
        });

        return $invoice->fresh();
    }
}

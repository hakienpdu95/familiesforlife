<?php

namespace Modules\Subscription\Observers;

use App\Foundation\BaseModelObserver;
use Illuminate\Database\Eloquent\Model;
use Modules\Subscription\Models\SubscriptionInvoice;

class SubscriptionInvoiceObserver extends BaseModelObserver
{
    protected function module(): string       { return 'subscription'; }
    protected function resourceCode(): string { return 'invoice'; }

    protected function subjectLabel(Model $m): ?string
    {
        /** @var SubscriptionInvoice $m */
        return $m->invoice_number;
    }

    protected function createdContext(Model $m): array
    {
        /** @var SubscriptionInvoice $m */
        return [
            'organization_id' => $m->organization_id,
            'amount'          => $m->amount,
            'currency'        => $m->currency,
            'status'          => $m->status?->label(),
        ];
    }

    protected function updatedContext(Model $m): array
    {
        /** @var SubscriptionInvoice $m */
        return [
            'organization_id' => $m->organization_id,
            'status'          => $m->status?->label(),
            'paid_at'         => $m->paid_at?->toDateTimeString(),
        ];
    }
}

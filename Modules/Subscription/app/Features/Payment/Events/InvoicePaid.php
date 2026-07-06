<?php

namespace Modules\Subscription\Features\Payment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Subscription\Models\SubscriptionInvoice;

class InvoicePaid
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly SubscriptionInvoice $invoice,
    ) {}
}

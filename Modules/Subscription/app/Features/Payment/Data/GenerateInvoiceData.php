<?php

namespace Modules\Subscription\Features\Payment\Data;

use Carbon\Carbon;
use Modules\Subscription\Enums\InvoiceType;
use Spatie\LaravelData\Data;

class GenerateInvoiceData extends Data
{
    public function __construct(
        public readonly int         $organizationId,
        public readonly int         $subscriptionId,
        public readonly int         $planId,
        public readonly float       $amount,
        public readonly string      $currency    = 'VND',
        public readonly InvoiceType $invoiceType = InvoiceType::Renewal,
        public readonly ?int        $newPlanId   = null,    // upgrade target plan
        public readonly ?string     $gateway     = null,
        public readonly ?Carbon     $billingPeriodStart = null,
        public readonly ?Carbon     $billingPeriodEnd   = null,
        public readonly ?Carbon     $dueDate     = null,
        public readonly ?string     $idempotentKey = null,
        public readonly ?string     $notes       = null,
    ) {}
}

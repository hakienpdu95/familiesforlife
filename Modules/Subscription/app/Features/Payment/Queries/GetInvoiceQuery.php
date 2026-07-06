<?php

namespace Modules\Subscription\Features\Payment\Queries;

use App\Shared\Contracts\QueryInterface;

class GetInvoiceQuery implements QueryInterface
{
    public function __construct(
        public readonly int  $invoiceId,
        public readonly bool $forAdmin = false,
    ) {}
}

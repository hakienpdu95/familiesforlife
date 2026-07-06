<?php

namespace Modules\Subscription\Features\Payment\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Subscription\Models\SubscriptionInvoice;

class GetInvoiceHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): SubscriptionInvoice
    {
        /** @var GetInvoiceQuery $query */
        $q = SubscriptionInvoice::withoutTenant()
            ->with(['plan:id,name,slug,currency', 'transactions']);

        if ($query->forAdmin) {
            $q->with('organization:id,name,slug');
        }

        return $q->findOrFail($query->invoiceId);
    }
}

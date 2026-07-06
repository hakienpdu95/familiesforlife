<?php

namespace Modules\Subscription\Features\Payment\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Subscription\Models\SubscriptionInvoice;

class ListInvoicesHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListInvoicesQuery $query */
        $q = SubscriptionInvoice::withoutTenant()
            ->with(['plan:id,name,slug', 'transactions'])
            ->when($query->organizationId, fn ($b) => $b->where('organization_id', $query->organizationId))
            ->when($query->status !== null, fn ($b) => $b->where('status', $query->status))
            ->when($query->search, fn ($b) => $b->where('invoice_number', 'like', "%{$query->search}%"))
            ->orderByDesc('created_at');

        if ($query->forAdmin) {
            $q->with('organization:id,name,slug');
        }

        return $q->paginate($query->perPage)->withQueryString();
    }
}

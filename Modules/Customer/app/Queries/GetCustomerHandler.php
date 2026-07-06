<?php
namespace Modules\Customer\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Customer\Models\Customer;

class GetCustomerHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Customer
    {
        /** @var GetCustomerQuery $query */
        $query->customer->load([
            'source:id,label,icon',
            'assignee:id,name',
            'tags',
            'meta.definition',
            'leads'      => fn ($q) => $q->with(['stage:id,label,color', 'assignee:id,name'])
                                         ->orderByDesc('created_at')
                                         ->limit(20),
            'activities'    => fn ($q) => $q->orderByDesc('created_at')->limit(50),
            'customerNotes' => fn ($q) => $q->orderByDesc('is_pinned')->orderByDesc('created_at'),
        ]);

        return $query->customer;
    }
}

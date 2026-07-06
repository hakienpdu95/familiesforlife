<?php

namespace Modules\Subscription\Features\Subscribe\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Laravelcm\Subscriptions\Models\Subscription;

class GetActiveSubscriptionHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): ?Subscription
    {
        /** @var GetActiveSubscriptionQuery $query */
        return Subscription::query()
            ->where('subscriber_id', $query->organizationId)
            ->where('slug', 'like', '%main%')
            ->whereNull('canceled_at')
            ->with(['plan.features'])
            ->latest('starts_at')
            ->first();
    }
}

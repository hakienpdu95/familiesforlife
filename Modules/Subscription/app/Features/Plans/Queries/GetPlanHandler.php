<?php

namespace Modules\Subscription\Features\Plans\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Laravelcm\Subscriptions\Models\Plan;

class GetPlanHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): ?Plan
    {
        /** @var GetPlanQuery $query */
        return Plan::query()
            ->when($query->withFeatures, fn ($q) => $q->with('features'))
            ->find($query->planId);
    }
}

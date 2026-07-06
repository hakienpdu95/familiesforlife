<?php

namespace Modules\Subscription\Features\Plans\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Database\Eloquent\Collection;
use Laravelcm\Subscriptions\Models\Plan;

class ListPlansHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        /** @var ListPlansQuery $query */
        return Plan::query()
            ->when($query->activeOnly,   fn ($q) => $q->where('is_active', true))
            ->when($query->publicOnly,   fn ($q) => $q->where('is_public', true))
            ->when($query->withFeatures, fn ($q) => $q->with('features'))
            ->orderBy('sort_order')
            ->get();
    }
}

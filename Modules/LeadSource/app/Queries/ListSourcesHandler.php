<?php

namespace Modules\LeadSource\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Support\Collection;
use Modules\LeadSource\Models\LeadSource;

class ListSourcesHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        /** @var ListSourcesQuery $query */
        $q = LeadSource::query()
            ->where(function ($q) use ($query) {
                $q->where('organization_id', $query->orgId)
                  ->orWhere('is_global', true);
            })
            ->orderBy('sort_order');

        if ($query->activeOnly) {
            $q->where('is_active', true);
        }

        return $q->get();
    }
}

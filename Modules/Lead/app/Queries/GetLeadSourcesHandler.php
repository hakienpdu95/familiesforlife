<?php

namespace Modules\Lead\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Support\Collection;
use Modules\LeadSource\Models\LeadSource;

class GetLeadSourcesHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        /** @var GetLeadSourcesQuery $query */
        $orgId = $query->orgId;

        return LeadSource::query()
            ->where(function ($q) use ($orgId) {
                $q->where('organization_id', $orgId)
                  ->orWhere('is_global', true);
            })
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}

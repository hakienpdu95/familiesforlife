<?php

namespace Modules\Lead\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Support\Collection;
use Modules\Lead\Models\LeadTagDefinition;

class ListTagsHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        /** @var ListTagsQuery $query */
        return LeadTagDefinition::where('organization_id', $query->orgId)
            ->orderBy('name')
            ->get();
    }
}

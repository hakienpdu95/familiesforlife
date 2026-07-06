<?php

namespace Modules\Lead\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Lead\Models\Lead;

class GetLeadHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Lead
    {
        /** @var GetLeadQuery $query */
        $lead = $query->lead;

        $lead->load([
            'stage',
            'source',
            'assignee:id,name',
            'contact',
            'tags',
            'customer:id,display_name,lifecycle_stage,customer_type',
            'notes'        => fn ($q) => $q->orderByDesc('is_pinned')->orderByDesc('created_at'),
            'activities'   => fn ($q) => $q->orderByDesc('created_at')->limit(50),
            'stageHistory',
        ]);

        return $lead;
    }
}

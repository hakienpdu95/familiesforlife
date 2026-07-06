<?php

namespace Modules\Lead\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\Lead\Enums\LeadStatus;
use Modules\Lead\Models\Lead;

class LeadKanbanHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): array
    {
        /** @var LeadKanbanQuery $query */
        $q = Lead::where('organization_id', $query->orgId)
            ->where('status', LeadStatus::Active->value)
            ->select([
                'id', 'stage_id', 'title',
                'contact_name', 'contact_company',
                'expected_value', 'lead_score', 'assigned_to',
                'expected_close_date', 'updated_at',
            ])
            ->with('assignee:id,name')
            ->orderByDesc('lead_score');

        if ($query->scopeUserId !== null) {
            $q->where('assigned_to', $query->scopeUserId);
        }

        return $q->limit(500)->get()->groupBy('stage_id')->all();
    }
}

<?php

namespace Modules\Lead\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Lead\Models\Lead;

class ListLeadsHandler implements QueryHandlerInterface
{
    private const SORTABLE = [
        'updated_at',
        'created_at',
        'lead_score',
        'expected_close_date',
        'expected_value',
        'last_activity_at',
    ];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListLeadsQuery $query */
        $q = Lead::query()
            ->select([
                'leads.id',
                'leads.title',
                'leads.contact_name',
                'leads.contact_phone',
                'leads.contact_company',
                'leads.stage_id',
                'leads.source_id',
                'leads.assigned_to',
                'leads.expected_value',
                'leads.currency',
                'leads.expected_close_date',
                'leads.lead_score',
                'leads.status',
                'leads.last_activity_at',
                'leads.activity_count',
                'leads.created_at',
                'leads.updated_at',
            ])
            ->with([
                'stage:id,label,color,probability',
                'source:id,label,icon,color',
                'assignee:id,name',
                'tags:id,name,color',
            ]);

        // Permission scope — Sales sees only their own leads
        if ($query->scopeUserId !== null) {
            $q->where('leads.assigned_to', $query->scopeUserId);
        }

        // Filters
        if ($query->stageId)    $q->where('stage_id', $query->stageId);
        if ($query->sourceId)   $q->where('source_id', $query->sourceId);
        if ($query->assignedTo) $q->where('assigned_to', $query->assignedTo);
        if ($query->status)     $q->where('status', $query->status);
        if ($query->minScore)   $q->where('lead_score', '>=', $query->minScore);

        if ($query->closingBefore) {
            $q->where('expected_close_date', '<=', $query->closingBefore);
        }
        if ($query->closingAfter) {
            $q->where('expected_close_date', '>=', $query->closingAfter);
        }

        if ($query->search) {
            $s = '%' . $query->search . '%';
            $q->where(fn ($sub) => $sub
                ->where('contact_name', 'like', $s)
                ->orWhere('contact_company', 'like', $s)
                ->orWhere('contact_phone', 'like', $s)
                ->orWhere('title', 'like', $s)
            );
        }

        if ($query->tagIds) {
            $q->whereExists(fn ($sub) => $sub
                ->select(DB::raw(1))
                ->from('lead_tag_map')
                ->whereColumn('lead_tag_map.lead_id', 'leads.id')
                ->whereIn('lead_tag_map.tag_id', $query->tagIds)
            );
        }

        $sortField = in_array($query->sortField, self::SORTABLE, true)
            ? $query->sortField
            : 'updated_at';
        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        return $q->orderBy($sortField, $sortDir)
            ->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}

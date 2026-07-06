<?php

namespace Modules\Lead\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Support\Facades\DB;
use Modules\Lead\Enums\LeadStatus;
use Modules\Lead\Models\Lead;

class LeadStatsHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): array
    {
        /** @var LeadStatsQuery $query */
        $base = Lead::where('organization_id', $query->orgId)
            ->when($query->scopeUserId, fn ($q) => $q->where('assigned_to', $query->scopeUserId))
            ->when($query->from, fn ($q) => $q->where('created_at', '>=', $query->from))
            ->when($query->to,   fn ($q) => $q->where('created_at', '<=', $query->to));

        return [
            'total_count'       => (clone $base)->count(),
            'by_status'         => (clone $base)
                ->groupBy('status')
                ->selectRaw('status, COUNT(*) as cnt')
                ->pluck('cnt', 'status'),
            'by_stage'          => (clone $base)
                ->groupBy('stage_id')
                ->selectRaw('stage_id, COUNT(*) as cnt')
                ->pluck('cnt', 'stage_id'),
            'total_value'       => (clone $base)
                ->where('status', LeadStatus::Active->value)
                ->sum('expected_value'),
            'weighted_value'    => $this->weightedPipelineValue($query),
            'conversion_rate'   => $this->conversionRate($query),
            'avg_days_to_close' => $this->avgTimeToClose($query),
        ];
    }

    private function weightedPipelineValue(LeadStatsQuery $query): float
    {
        return (float) DB::table('leads')
            ->join('lead_pipeline_stages', 'leads.stage_id', '=', 'lead_pipeline_stages.id')
            ->where('leads.organization_id', $query->orgId)
            ->where('leads.status', LeadStatus::Active->value)
            ->whereNull('leads.deleted_at')
            ->when($query->scopeUserId, fn ($q) => $q->where('leads.assigned_to', $query->scopeUserId))
            ->when($query->from, fn ($q) => $q->where('leads.created_at', '>=', $query->from))
            ->when($query->to,   fn ($q) => $q->where('leads.created_at', '<=', $query->to))
            ->selectRaw('COALESCE(SUM(leads.expected_value * lead_pipeline_stages.probability / 100), 0) as v')
            ->value('v');
    }

    private function conversionRate(LeadStatsQuery $query): float
    {
        $total = Lead::where('organization_id', $query->orgId)
            ->when($query->scopeUserId, fn ($q) => $q->where('assigned_to', $query->scopeUserId))
            ->when($query->from, fn ($q) => $q->where('created_at', '>=', $query->from))
            ->when($query->to,   fn ($q) => $q->where('created_at', '<=', $query->to))
            ->count();

        if (! $total) return 0.0;

        $won = Lead::where('organization_id', $query->orgId)
            ->where('status', LeadStatus::Converted->value)
            ->when($query->scopeUserId, fn ($q) => $q->where('assigned_to', $query->scopeUserId))
            ->when($query->from, fn ($q) => $q->where('created_at', '>=', $query->from))
            ->when($query->to,   fn ($q) => $q->where('created_at', '<=', $query->to))
            ->count();

        return round($won / $total * 100, 2);
    }

    private function avgTimeToClose(LeadStatsQuery $query): ?float
    {
        return Lead::where('organization_id', $query->orgId)
            ->where('status', LeadStatus::Converted->value)
            ->whereNotNull('actual_close_date')
            ->when($query->scopeUserId, fn ($q) => $q->where('assigned_to', $query->scopeUserId))
            ->when($query->from, fn ($q) => $q->where('created_at', '>=', $query->from))
            ->when($query->to,   fn ($q) => $q->where('created_at', '<=', $query->to))
            ->selectRaw('AVG(DATEDIFF(actual_close_date, DATE(created_at))) as days')
            ->value('days');
    }
}

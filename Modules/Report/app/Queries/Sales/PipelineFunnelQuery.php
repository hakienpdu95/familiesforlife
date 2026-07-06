<?php

namespace Modules\Report\Queries\Sales;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Modules\Lead\Models\Lead;
use Modules\Lead\Models\LeadStageHistory;

final class PipelineFunnelQuery
{
    public function __construct(
        private readonly int     $orgId,
        private readonly string  $dateFrom,
        private readonly string  $dateTo,
        private readonly ?int    $assignedTo = null,
        private readonly ?int    $sourceId   = null,
    ) {}

    public static function fromRequest(array $params): self
    {
        return new self(
            orgId:      TenantContext::getOrganizationId(),
            dateFrom:   $params['date_from']   ?? now()->startOfMonth()->toDateString(),
            dateTo:     $params['date_to']     ?? now()->toDateString(),
            assignedTo: !empty($params['assigned_to']) ? (int) $params['assigned_to'] : null,
            sourceId:   !empty($params['source_id'])   ? (int) $params['source_id']   : null,
        );
    }

    private function baseLead()
    {
        return Lead::withoutTenant()
            ->where('leads.organization_id', $this->orgId)
            ->whereBetween('leads.created_at', [$this->dateFrom . ' 00:00:00', $this->dateTo . ' 23:59:59'])
            ->when($this->assignedTo, fn ($q) => $q->where('leads.assigned_to', $this->assignedTo))
            ->when($this->sourceId,   fn ($q) => $q->where('leads.source_id',   $this->sourceId));
    }

    public function summary(): array
    {
        $base = $this->baseLead();

        $stats = (clone $base)
            ->selectRaw("
                COUNT(*) as total_leads,
                SUM(expected_value) as total_expected_value,
                AVG(lead_score) as avg_lead_score,
                SUM(lead_score >= 80) as hot_leads_count,
                SUM(last_activity_at < DATE_SUB(NOW(), INTERVAL 14 DAY) AND status NOT IN ('won','lost')) as stale_leads_count,
                SUM(status = 'won') as won_count,
                SUM(status = 'lost') as lost_count,
                SUM(status = 'won' OR status = 'lost') as closed_count
            ")
            ->first();

        $totalClosed = (int) ($stats->closed_count ?? 0);
        $wonCount    = (int) ($stats->won_count ?? 0);
        $winRate     = $totalClosed > 0 ? round($wonCount / $totalClosed * 100, 1) : 0;

        return [
            'total_leads'         => (int) ($stats->total_leads ?? 0),
            'total_expected_value'=> (float) ($stats->total_expected_value ?? 0),
            'avg_lead_score'      => round((float) ($stats->avg_lead_score ?? 0), 1),
            'hot_leads_count'     => (int) ($stats->hot_leads_count ?? 0),
            'stale_leads_count'   => (int) ($stats->stale_leads_count ?? 0),
            'win_rate_pct'        => $winRate,
            'currency'            => 'VND',
        ];
    }

    public function funnel(): Collection
    {
        $stageCounts = $this->baseLead()
            ->join('lead_pipeline_stages', 'lead_pipeline_stages.id', '=', 'leads.stage_id')
            ->selectRaw('
                leads.stage_id,
                lead_pipeline_stages.code as stage_code,
                lead_pipeline_stages.label,
                lead_pipeline_stages.sort_order,
                lead_pipeline_stages.is_won,
                lead_pipeline_stages.is_lost,
                lead_pipeline_stages.probability,
                COUNT(leads.id) as count,
                COALESCE(SUM(leads.expected_value), 0) as value
            ')
            ->groupBy('leads.stage_id', 'stage_code', 'label', 'sort_order', 'is_won', 'is_lost', 'probability')
            ->orderBy('sort_order')
            ->get();

        // Avg days a lead spends in each stage (stage_from_id = stage being exited).
        // Time in stage = from when lead entered it (prev changed_at or lead created_at) to when it left (current changed_at).
        $avgDays = LeadStageHistory::from('lead_stage_history as lsh')
            ->join('leads', 'leads.id', '=', 'lsh.lead_id')
            ->where('leads.organization_id', $this->orgId)
            ->whereNotNull('lsh.stage_from_id')
            ->selectRaw('
                lsh.stage_from_id as stage_id,
                AVG(TIMESTAMPDIFF(HOUR,
                    COALESCE(
                        (SELECT MAX(lsh2.changed_at)
                         FROM lead_stage_history lsh2
                         WHERE lsh2.lead_id = lsh.lead_id
                           AND lsh2.stage_to_id = lsh.stage_from_id
                           AND lsh2.changed_at < lsh.changed_at),
                        leads.created_at
                    ),
                    lsh.changed_at
                ) / 24) as avg_days
            ')
            ->groupBy('lsh.stage_from_id')
            ->pluck('avg_days', 'stage_id');

        $total = $stageCounts->sum('count');

        return $stageCounts->map(fn ($s) => [
            'stage_id'             => $s->stage_id,
            'stage_code'           => $s->stage_code,
            'label'                => $s->label,
            'is_won'               => (bool) $s->is_won,
            'is_lost'              => (bool) $s->is_lost,
            'probability'          => (int) $s->probability,
            'count'                => (int) $s->count,
            'value'                => (float) $s->value,
            'avg_days_in_stage'    => round((float) ($avgDays[$s->stage_id] ?? 0), 1),
            'pct_of_total'         => $total > 0 ? round($s->count / $total * 100, 1) : 0,
        ]);
    }

    public function bySource(): Collection
    {
        return $this->baseLead()
            ->join('lead_sources', 'lead_sources.id', '=', 'leads.source_id')
            ->selectRaw('
                leads.source_id,
                lead_sources.code as source_code,
                lead_sources.label,
                COUNT(*) as count,
                COALESCE(SUM(leads.expected_value), 0) as value,
                SUM(leads.status = "won") as won_count
            ')
            ->groupBy('leads.source_id', 'source_code', 'label')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => [
                'source_id'    => $r->source_id,
                'source_code'  => $r->source_code,
                'label'        => $r->label,
                'count'        => (int) $r->count,
                'value'        => (float) $r->value,
                'win_rate_pct' => $r->count > 0 ? round($r->won_count / $r->count * 100, 1) : 0,
            ]);
    }

    public function byAssignee(): Collection
    {
        return $this->baseLead()
            ->join('users', 'users.id', '=', 'leads.assigned_to')
            ->selectRaw('
                users.id as user_id, users.name,
                COUNT(*) as total,
                SUM(leads.status = "won") as won,
                SUM(leads.status = "lost") as lost,
                COALESCE(SUM(leads.expected_value), 0) as pipeline_value
            ')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'user_id'        => $r->user_id,
                'name'           => $r->name,
                'total'          => (int) $r->total,
                'won'            => (int) $r->won,
                'lost'           => (int) $r->lost,
                'win_rate_pct'   => $r->total > 0 ? round($r->won / $r->total * 100, 1) : 0,
                'pipeline_value' => (float) $r->pipeline_value,
            ]);
    }

    public function winLossSummary(): array
    {
        $base = $this->baseLead();

        $won = (clone $base)
            ->where('leads.status', 'won')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(actual_value), 0) as value')
            ->first();

        $lost = (clone $base)
            ->where('leads.status', 'lost')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(expected_value), 0) as value')
            ->first();

        $wonCount  = (int) ($won->count ?? 0);
        $lostCount = (int) ($lost->count ?? 0);
        $total     = $wonCount + $lostCount;

        return [
            'won'               => $wonCount,
            'won_value'         => (float) ($won->value ?? 0),
            'lost'              => $lostCount,
            'lost_value'        => (float) ($lost->value ?? 0),
            'overall_win_rate_pct' => $total > 0 ? round($wonCount / $total * 100, 1) : 0,
        ];
    }

    public function trend(): Collection
    {
        return $this->baseLead()
            ->selectRaw("
                DATE_FORMAT(leads.created_at, '%Y-%m') as period,
                COUNT(*) as new_leads,
                SUM(leads.status = 'won') as won,
                SUM(leads.status = 'lost') as lost,
                COALESCE(SUM(CASE WHEN leads.status='won' THEN leads.actual_value ELSE 0 END), 0) as value_won
            ")
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }
}

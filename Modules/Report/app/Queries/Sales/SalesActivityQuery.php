<?php

namespace Modules\Report\Queries\Sales;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Modules\Lead\Models\LeadActivity;

final class SalesActivityQuery
{
    public function __construct(
        private readonly int    $orgId,
        private readonly string $dateFrom,
        private readonly string $dateTo,
        private readonly ?int   $assignedTo = null,
    ) {}

    public static function fromRequest(array $params): self
    {
        return new self(
            orgId:      TenantContext::getOrganizationId(),
            dateFrom:   $params['date_from']   ?? now()->subDays(29)->toDateString(),
            dateTo:     $params['date_to']     ?? now()->toDateString(),
            assignedTo: $params['assigned_to'] ? (int) $params['assigned_to'] : null,
        );
    }

    private function base()
    {
        return LeadActivity::where('lead_activities.organization_id', $this->orgId)
            ->whereBetween('lead_activities.created_at', [
                $this->dateFrom . ' 00:00:00',
                $this->dateTo   . ' 23:59:59',
            ])
            ->when($this->assignedTo, fn ($q) => $q->where('lead_activities.created_by', $this->assignedTo));
    }

    public function summary(): array
    {
        $counts = (clone $this->base())
            ->selectRaw("
                COUNT(*) as total,
                SUM(type = 'call')    as calls,
                SUM(type = 'email')   as emails,
                SUM(type = 'meeting') as meetings,
                SUM(type = 'demo')    as demos,
                SUM(type = 'note')    as notes
            ")
            ->first();

        return [
            'total_activities' => (int) ($counts->total    ?? 0),
            'calls'            => (int) ($counts->calls    ?? 0),
            'emails'           => (int) ($counts->emails   ?? 0),
            'meetings'         => (int) ($counts->meetings ?? 0),
            'demos'            => (int) ($counts->demos    ?? 0),
            'notes'            => (int) ($counts->notes    ?? 0),
        ];
    }

    public function byAssignee(): Collection
    {
        return (clone $this->base())
            ->join('users', 'users.id', '=', 'lead_activities.created_by')
            ->selectRaw("
                users.id as user_id, users.name,
                COUNT(*) as activities,
                SUM(type = 'call')    as calls,
                SUM(type = 'email')   as emails,
                SUM(type = 'meeting') as meetings
            ")
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('activities')
            ->limit(15)
            ->get();
    }

    public function byType(): Collection
    {
        return (clone $this->base())
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderByDesc('count')
            ->get();
    }

    public function byDay(): Collection
    {
        return (clone $this->base())
            ->selectRaw("DATE(lead_activities.created_at) as date, COUNT(*) as count")
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    public function leadResponseTime(): array
    {
        $first = LeadActivity::where('lead_activities.organization_id', $this->orgId)
            ->where('lead_activities.type', 'call')
            ->join('leads', 'leads.id', '=', 'lead_activities.lead_id')
            ->whereRaw('lead_activities.created_at = (SELECT MIN(la2.created_at) FROM lead_activities la2 WHERE la2.lead_id = leads.id AND la2.type = "call")')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, leads.created_at, lead_activities.created_at)) as avg_hrs')
            ->value('avg_hrs');

        return [
            'avg_first_response_hours' => round((float) ($first ?? 0), 1),
        ];
    }
}

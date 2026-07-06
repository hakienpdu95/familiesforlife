<?php

namespace Modules\Report\Queries\Sales;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Modules\Lead\Models\Lead;

final class ConversionRateQuery
{
    public function __construct(
        private readonly int    $orgId,
        private readonly string $dateFrom,
        private readonly string $dateTo,
    ) {}

    public static function fromRequest(array $params): self
    {
        return new self(
            orgId:    TenantContext::getOrganizationId(),
            dateFrom: $params['date_from'] ?? now()->startOfMonth()->toDateString(),
            dateTo:   $params['date_to']   ?? now()->toDateString(),
        );
    }

    private function base()
    {
        return Lead::withoutTenant()
            ->where('leads.organization_id', $this->orgId)
            ->whereBetween('leads.created_at', [$this->dateFrom . ' 00:00:00', $this->dateTo . ' 23:59:59']);
    }

    public function overall(): array
    {
        $stats = $this->base()
            ->selectRaw("
                COUNT(*) as total_leads,
                SUM(customer_id IS NOT NULL) as converted,
                AVG(CASE WHEN customer_id IS NOT NULL THEN DATEDIFF(actual_close_date, leads.created_at) END) as avg_days
            ")
            ->first();

        $total     = (int) ($stats->total_leads ?? 0);
        $converted = (int) ($stats->converted ?? 0);

        return [
            'total_leads'            => $total,
            'converted_to_customer'  => $converted,
            'conversion_rate_pct'    => $total > 0 ? round($converted / $total * 100, 1) : 0,
            'avg_days_to_convert'    => round((float) ($stats->avg_days ?? 0), 1),
        ];
    }

    public function bySource(): Collection
    {
        return $this->base()
            ->leftJoin('lead_sources', 'lead_sources.id', '=', 'leads.source_id')
            ->selectRaw('
                COALESCE(lead_sources.code, "unknown") as source_code,
                COALESCE(lead_sources.label, "Không rõ") as label,
                COUNT(*) as leads,
                SUM(leads.customer_id IS NOT NULL) as converted
            ')
            ->groupBy('source_code', 'label')
            ->orderByDesc('leads')
            ->get()
            ->map(fn ($r) => [
                'source_code'  => $r->source_code,
                'label'        => $r->label,
                'leads'        => (int) $r->leads,
                'converted'    => (int) $r->converted,
                'rate_pct'     => $r->leads > 0 ? round($r->converted / $r->leads * 100, 1) : 0,
            ]);
    }

    public function byScoreBand(): array
    {
        $bands = [
            ['label' => 'Hot (80-100)', 'min' => 80,  'max' => 100],
            ['label' => 'Warm (50-79)', 'min' => 50,  'max' => 79],
            ['label' => 'Cold (0-49)',  'min' => 0,   'max' => 49],
        ];

        return collect($bands)->map(function ($band) {
            $row = $this->base()
                ->whereBetween('lead_score', [$band['min'], $band['max']])
                ->selectRaw('COUNT(*) as leads, SUM(customer_id IS NOT NULL) as converted')
                ->first();

            $leads     = (int) ($row->leads ?? 0);
            $converted = (int) ($row->converted ?? 0);

            return [
                'band'      => $band['label'],
                'leads'     => $leads,
                'converted' => $converted,
                'rate_pct'  => $leads > 0 ? round($converted / $leads * 100, 1) : 0,
            ];
        })->all();
    }

    public function monthlyCohort(): Collection
    {
        return $this->base()
            ->selectRaw("
                DATE_FORMAT(leads.created_at, '%Y-%m') as cohort_month,
                COUNT(*) as leads_created,
                SUM(customer_id IS NOT NULL AND DATEDIFF(actual_close_date, leads.created_at) <= 30) as converted_within_30d
            ")
            ->groupBy('cohort_month')
            ->orderBy('cohort_month')
            ->get()
            ->map(fn ($r) => [
                'cohort_month'         => $r->cohort_month,
                'leads_created'        => (int) $r->leads_created,
                'converted_within_30d' => (int) $r->converted_within_30d,
                'rate_30d_pct'         => $r->leads_created > 0
                    ? round($r->converted_within_30d / $r->leads_created * 100, 1)
                    : 0,
            ]);
    }
}

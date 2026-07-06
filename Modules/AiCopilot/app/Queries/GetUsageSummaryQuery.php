<?php

namespace Modules\AiCopilot\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\AiCopilot\Models\AiMonthlyUsage;
use Modules\AiCopilot\Models\AiRequest;

class GetUsageSummaryQuery
{
    public function __construct() {}

    public function run(): array
    {
        $now       = now();
        $yearMonth = $now->format('Y-m');

        // ── Current month totals (from aggregate) ─────────────────────────
        // OrganizationScope (global) handles tenant filtering — no need for explicit org_id
        $monthTotal = AiMonthlyUsage::where('year_month', $yearMonth)
            ->whereNull('agent_id')
            ->first();

        $requestsThisMonth = (int) ($monthTotal->total_requests      ?? 0);
        $tokensThisMonth   = (int) ($monthTotal->total_tokens         ?? 0);
        $costThisMonth     = (float) ($monthTotal->total_cost_usd     ?? 0);
        $successThisMonth  = (int) ($monthTotal->successful_requests  ?? 0);

        // ── Quota remaining (subscription system) ─────────────────────────
        $requestsRemaining = org_quota('quota.ai_requests');
        $tokensRemaining   = org_quota('quota.ai_tokens');
        $requestsLimit     = $requestsRemaining + $requestsThisMonth;
        $tokensLimit       = $tokensRemaining + $tokensThisMonth;

        // ── 6-month trend ──────────────────────────────────────────────────
        $trend = AiMonthlyUsage::whereNull('agent_id')
            ->where('year_month', '>=', $now->copy()->subMonths(5)->format('Y-m'))
            ->orderBy('year_month')
            ->get(['year_month', 'total_requests', 'total_tokens', 'total_cost_usd', 'successful_requests'])
            ->map(fn ($r) => [
                'month'    => $r->year_month,
                'requests' => (int) $r->total_requests,
                'tokens'   => (int) $r->total_tokens,
                'cost'     => round((float) $r->total_cost_usd, 4),
                'success'  => (int) $r->successful_requests,
            ])
            ->values();

        // ── Per-agent breakdown (current month) ────────────────────────────
        $byAgent = AiMonthlyUsage::where('year_month', $yearMonth)
            ->whereNotNull('agent_id')
            ->with('agent:id,name,slug,task_type')
            ->orderByDesc('total_requests')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'agent_id'   => $r->agent_id,
                'agent_name' => $r->agent?->name ?? 'Unknown',
                'agent_slug' => $r->agent?->slug ?? '—',
                'task_type'  => $r->agent?->task_type ?? '—',
                'requests'   => (int) $r->total_requests,
                'tokens'     => (int) $r->total_tokens,
                'cost'       => round((float) $r->total_cost_usd, 4),
            ]);

        // ── Status breakdown (current month live count) ────────────────────
        $statusCounts = AiRequest::where('created_at', '>=', $now->copy()->startOfMonth())
            ->select('status', DB::raw('count(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        // ── Recent requests ────────────────────────────────────────────────
        $recentRequests = AiRequest::with('agent:id,name,slug')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'uuid', 'agent_id', 'status', 'model', 'provider',
                   'total_tokens', 'cost_usd', 'duration_ms', 'created_at', 'completed_at', 'error_message']);

        return compact(
            'requestsThisMonth', 'tokensThisMonth', 'costThisMonth', 'successThisMonth',
            'requestsRemaining', 'tokensRemaining',
            'requestsLimit', 'tokensLimit',
            'trend', 'byAgent', 'statusCounts', 'recentRequests',
        );
    }
}

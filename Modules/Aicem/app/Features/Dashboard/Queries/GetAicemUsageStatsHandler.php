<?php

namespace Modules\Aicem\Features\Dashboard\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use App\Shared\Tenancy\TenantContext;
use Modules\Aicem\Models\AicemGenerationRun;
use Modules\Aicem\Models\AicemMonthlyBudgetUsage;
use Modules\Aicem\Models\AicemWorkflow;

/**
 * Thống kê gọn cho CEO/System Admin xem — tính trực tiếp từ aicem_generation_runs (không chỉ
 * đọc aicem_monthly_budget_usage, vì bảng đó CHỈ tồn tại khi Organization có đặt hạn mức —
 * mục 13.1) để vẫn có số liệu hữu ích ngay cả khi Organization chưa cấu hình budget.
 */
class GetAicemUsageStatsHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): array
    {
        $monthStart = now()->startOfMonth();
        $baseQuery  = fn () => AicemGenerationRun::query()->where('created_at', '>=', $monthStart);

        $organization = TenantContext::resolve();
        $yearMonth    = now()->format('Y-m');

        $budgetUsage = AicemMonthlyBudgetUsage::query()->where('year_month', $yearMonth)->first();

        return [
            'total_runs_this_month'     => $baseQuery()->count(),
            'succeeded_runs_this_month' => $baseQuery()->where('status', 'succeeded')->count(),
            'failed_runs_this_month'    => $baseQuery()->where('status', 'failed')->count(),
            'cost_this_month'           => (float) $baseQuery()->where('status', 'succeeded')->sum('cost_usd'),
            // Phase 6 (mục 8.7/15) — token đọc từ cache Anthropic tháng này, tín hiệu prompt
            // caching có đang phát huy tác dụng không (không quy đổi $ chính xác ở đây vì cần giá
            // theo từng model/run riêng — xem cache_read_tokens trên từng aicem_generation_runs).
            'cache_read_tokens_this_month'     => (int) $baseQuery()->where('status', 'succeeded')->sum('cache_read_tokens'),
            'cache_creation_tokens_this_month' => (int) $baseQuery()->where('status', 'succeeded')->sum('cache_creation_tokens'),
            'budget_limit'              => $organization->ai_monthly_budget_usd !== null ? (float) $organization->ai_monthly_budget_usd : null,
            'budget_reserved'           => $budgetUsage ? (float) $budgetUsage->reserved_usd : 0.0,
            'top_workflows'             => AicemWorkflow::query()
                ->withCount(['generationRuns' => fn ($q) => $q->where('created_at', '>=', $monthStart)])
                ->orderByDesc('generation_runs_count')
                ->limit(5)
                ->get(),
            'recent_runs' => AicemGenerationRun::query()
                ->with(['workflow:id,name,subject_type', 'requestedBy:id,name'])
                ->latest()
                ->limit(20)
                ->get(),
        ];
    }
}

<?php

namespace Modules\Aicem\Features\Dashboard\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Modules\Aicem\Models\AicemGenerationRun;
use Modules\Aicem\Models\AicemMonthlyBudgetUsage;
use Modules\Aicem\Models\AicemWorkflow;

/**
 * Thống kê gọn cho CEO/System Admin xem — tính trực tiếp từ aicem_generation_runs (không chỉ
 * đọc aicem_monthly_budget_usage, vì bảng đó CHỈ tồn tại khi Organization có đặt hạn mức —
 * mục 13.1) để vẫn có số liệu hữu ích ngay cả khi Organization chưa cấu hình budget.
 *
 * `cost_this_month` PHẢI cộng thêm chi phí "Layer 2" của CoreIdeaExtractor (bảng riêng
 * `cie_layer2_runs`, xem RunLayer2ExtractionAction) — Layer 2 gọi AI trừ đúng vào CÙNG ngân sách
 * tổ chức mà Aicem dùng (aicem_monthly_budget_usage), nhưng KHÔNG tạo dòng nào ở
 * aicem_generation_runs (không có khái niệm workflow/subject), nên nếu chỉ sum() bảng đó thì con
 * số hiển thị sẽ THẤP HƠN thực tế đã chi khi tổ chức có dùng cả 2 tính năng.
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

        $coreIdeaLayer2CostThisMonth = (float) DB::table('cie_layer2_runs')
            ->where('organization_id', $organization->id)
            ->where('created_at', '>=', $monthStart)
            ->sum('cost_usd');

        return [
            'suggestion_acceptance' => $this->buildSuggestionAcceptance($organization->id),
            'total_runs_this_month'     => $baseQuery()->count(),
            'succeeded_runs_this_month' => $baseQuery()->where('status', 'succeeded')->count(),
            'failed_runs_this_month'    => $baseQuery()->where('status', 'failed')->count(),
            'cost_this_month'           => (float) $baseQuery()->where('status', 'succeeded')->sum('cost_usd') + $coreIdeaLayer2CostThisMonth,
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

    /**
     * Đối chiếu bài context-engineering (animalz.co) — "evaluation": trước đây `AicemSuggestion.
     * status` chỉ dùng để apply/discard (AcceptSuggestionAction/RejectSuggestionAction), không hề
     * tổng hợp thành insight nào — không ai biết ngữ cảnh hiện tại có thực sự giúp ích không. Toàn
     * thời gian (không giới hạn theo tháng như phần còn lại của dashboard) vì mẫu cần đủ lớn mới
     * có ý nghĩa thống kê — AicemSuggestion không extends TenantAwareModel (xem docblock model),
     * lọc tay theo organization_id thay vì dựa global scope.
     */
    private function buildSuggestionAcceptance(int $organizationId): array
    {
        $rows = DB::table('aicem_suggestions')
            ->where('organization_id', $organizationId)
            ->selectRaw("COALESCE(field, 'block') as field_key, status, COUNT(*) as cnt")
            ->groupBy('field_key', 'status')
            ->get();

        $overall = ['accepted' => 0, 'rejected' => 0, 'pending' => 0, 'stale' => 0];
        $byField = [];

        foreach ($rows as $row) {
            $overall[$row->status] = ($overall[$row->status] ?? 0) + $row->cnt;
            $byField[$row->field_key][$row->status] = ($byField[$row->field_key][$row->status] ?? 0) + $row->cnt;
        }

        $decided = $overall['accepted'] + $overall['rejected'];

        return [
            'accepted'        => $overall['accepted'],
            'rejected'        => $overall['rejected'],
            'pending'         => $overall['pending'],
            'stale'           => $overall['stale'],
            'acceptance_rate' => $decided > 0 ? round($overall['accepted'] / $decided * 100, 1) : null,
            'by_field'        => collect($byField)
                ->map(function (array $counts, string $fieldKey): array {
                    $decided = ($counts['accepted'] ?? 0) + ($counts['rejected'] ?? 0);

                    return [
                        'field'    => $fieldKey,
                        'accepted' => $counts['accepted'] ?? 0,
                        'rejected' => $counts['rejected'] ?? 0,
                        'pending'  => $counts['pending'] ?? 0,
                        'rate'     => $decided > 0 ? round(($counts['accepted'] ?? 0) / $decided * 100, 1) : null,
                    ];
                })
                ->sortByDesc(fn (array $r) => $r['accepted'] + $r['rejected'] + $r['pending'])
                ->values()
                ->all(),
        ];
    }
}

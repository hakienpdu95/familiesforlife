<?php

namespace Modules\VideoIdeaExtractor\Features\TranscriptExtraction\Actions;

use App\Services\AI\CostCalculator;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Support\Facades\DB;
use Modules\VideoIdeaExtractor\Features\TranscriptExtraction\Exceptions\AiBudgetExceededException;

/**
 * Tương đương CheckCoreIdeaAiBudgetAction bên CoreIdeaExtractor — check + ghi nhận chi phí AI vào
 * CÙNG ngân sách tổ chức mà Aicem/CoreIdeaExtractor dùng (bảng `aicem_monthly_budget_usage`,
 * ngân sách AI DÙNG CHUNG cấp tổ chức/tháng cho MỌI tính năng gọi AI, không phải riêng module nào).
 */
class CheckVideoIdeaAiBudgetAction
{
    private const TABLE = 'aicem_monthly_budget_usage';

    /** Ước lượng & chặn TRƯỚC khi gọi AI nếu chắc chắn sẽ vượt hạn mức tháng hiện tại. */
    public function ensureWithinBudget(Organization $organization, int $estimatedInputTokens, int $maxOutputTokens, string $provider, string $model): void
    {
        if ($organization->ai_monthly_budget_usd === null) {
            return;
        }

        $estimated = CostCalculator::calculate($provider, $model, $estimatedInputTokens, $maxOutputTokens);
        $usedSoFar = $this->currentUsage($organization->id);

        if ($usedSoFar + $estimated > (float) $organization->ai_monthly_budget_usd) {
            throw new AiBudgetExceededException(sprintf(
                'Đã/sắp vượt hạn mức chi phí AI tháng %s ($%.4f đã dùng + $%.4f ước tính cho lần chạy này > hạn mức $%.2f/tháng). Xem/đổi hạn mức ở "Cấu hình AICEM".',
                now()->format('Y-m'), $usedSoFar, $estimated, (float) $organization->ai_monthly_budget_usd
            ));
        }
    }

    /** Ghi nhận chi phí THẬT vào `settled_usd` sau khi gọi AI thành công. */
    public function recordActualCost(Organization $organization, float $actualCostUsd): void
    {
        $yearMonth = now()->format('Y-m');

        $existing = DB::table(self::TABLE)
            ->where('organization_id', $organization->id)
            ->where('year_month', $yearMonth)
            ->first();

        if ($existing === null) {
            DB::table(self::TABLE)->insert([
                'organization_id' => $organization->id,
                'year_month'      => $yearMonth,
                'reserved_usd'    => 0,
                'settled_usd'     => $actualCostUsd,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            return;
        }

        if ($existing->deleted_at !== null) {
            DB::table(self::TABLE)->where('id', $existing->id)->update([
                'deleted_at'  => null,
                'settled_usd' => (float) $existing->settled_usd + $actualCostUsd,
                'updated_at'  => now(),
            ]);

            return;
        }

        DB::table(self::TABLE)
            ->where('id', $existing->id)
            ->update([
                'settled_usd' => (float) $existing->settled_usd + $actualCostUsd,
                'updated_at'  => now(),
            ]);
    }

    private function currentUsage(int $organizationId): float
    {
        $row = DB::table(self::TABLE)
            ->where('organization_id', $organizationId)
            ->where('year_month', now()->format('Y-m'))
            ->whereNull('deleted_at')
            ->first();

        return $row ? ((float) $row->reserved_usd + (float) $row->settled_usd) : 0.0;
    }
}

<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Actions;

use App\Services\AI\CostCalculator;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Support\Facades\DB;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Exceptions\AiBudgetExceededException;

/**
 * Check + ghi nhận chi phí AI của "Layer 2" (2026-07-28) vào CÙNG ngân sách tổ chức mà Aicem
 * dùng (bảng `aicem_monthly_budget_usage`, cột `organizations.ai_monthly_budget_usd`) — đây là
 * ngân sách AI DÙNG CHUNG cấp tổ chức/tháng cho MỌI tính năng gọi AI, không phải riêng của
 * module Aicem, dù bảng mang tiền tố "aicem_" (đặt tên lịch sử, xem
 * Modules/Aicem/app/Models/AicemMonthlyBudgetUsage.php). Nếu không ghi nhận vào đây, dashboard
 * "Tổng quan" của Aicem sẽ báo cáo THIẾU chi phí thật đã dùng.
 *
 * KHÔNG dùng lại CheckAndReserveBudgetAction/ReconcileBudgetAction của Aicem — 2 action đó bắt
 * buộc 1 AicemGenerationRun instance (đọc thẳng $run->provider/$run->model/$run->organization_id),
 * trong khi ở đây chỉ có 1 lần gọi AI đơn lẻ do người dùng bấm nút thủ công, không có khái niệm
 * "generation run" nào. Cũng ĐƠN GIẢN HƠN pattern reserve→release/settle 2 pha của Aicem (không
 * cần transaction lockForUpdate) vì đây là request đồng bộ, tần suất thấp (người dùng tự bấm),
 * không có nhiều job nền chạy đồng thời như Aicem — chỉ ước lượng-chặn TRƯỚC khi gọi AI, rồi ghi
 * nhận chi phí THẬT thẳng SAU khi gọi thành công (không cần ước lượng nữa).
 */
class CheckCoreIdeaAiBudgetAction
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

        // Hàng đã soft-delete (không nên xảy ra trong vận hành bình thường, phòng vệ giống
        // CheckAndReserveBudgetAction của Aicem) — restore thay vì insert mới (unique
        // organization_id+year_month sẽ chặn insert mới trong khi hàng cũ vẫn coi là "tồn tại").
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

<?php

namespace Modules\Aicem\Features\Generation\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Aicem\Models\AicemGenerationRun;
use Modules\Aicem\Models\AicemMonthlyBudgetUsage;

/**
 * Reconcile sau khi có kết quả thật — spec/AICEM_Technical_Specification.md mục 13.1.
 * Thành công: trừ reserved, cộng settled bằng cost thật. Fail/timeout: chỉ trừ reserved (nhả
 * reservation), KHÔNG cộng settled — 1 run hỏng không "ăn" hạn mức vĩnh viễn.
 *
 * No-op nếu $run->estimated_cost_usd === null (org không đặt budget lúc reserve — không có gì
 * để reconcile, xem CheckAndReserveBudgetAction).
 */
class ReconcileBudgetAction
{
    use AsAction;

    public function settle(AicemGenerationRun $run, float $actualCost): void
    {
        $this->adjust($run, fn (AicemMonthlyBudgetUsage $usage) => $usage->update([
            'reserved_usd' => max(0, (float) $usage->reserved_usd - (float) $run->estimated_cost_usd),
            'settled_usd'  => (float) $usage->settled_usd + $actualCost,
        ]));
    }

    public function release(AicemGenerationRun $run): void
    {
        $this->adjust($run, fn (AicemMonthlyBudgetUsage $usage) => $usage->update([
            'reserved_usd' => max(0, (float) $usage->reserved_usd - (float) $run->estimated_cost_usd),
        ]));
    }

    private function adjust(AicemGenerationRun $run, \Closure $mutator): void
    {
        if ($run->estimated_cost_usd === null) {
            return;
        }

        DB::transaction(function () use ($run, $mutator) {
            // withTrashed() — cùng lý do với CheckAndReserveBudgetAction: hàng đếm không nên bị
            // soft-delete trong vận hành bình thường, nhưng nếu có thì vẫn phải reconcile đúng vào
            // đúng hàng đó (không tạo hàng mới), không âm thầm bỏ qua.
            $usage = AicemMonthlyBudgetUsage::withTrashed()
                ->where('organization_id', $run->organization_id)
                ->where('year_month', $run->created_at->format('Y-m'))
                ->lockForUpdate()
                ->first();

            if ($usage) {
                $mutator($usage);
            }
        });
    }
}

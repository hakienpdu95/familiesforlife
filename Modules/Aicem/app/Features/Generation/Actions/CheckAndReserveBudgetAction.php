<?php

namespace Modules\Aicem\Features\Generation\Actions;

use App\Services\AI\CostCalculator;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Aicem\Features\Generation\Exceptions\BudgetExceededException;
use Modules\Aicem\Models\AicemGenerationRun;
use Modules\Aicem\Models\AicemMonthlyBudgetUsage;

/**
 * Reserve trần chi phí ước lượng TRƯỚC khi gọi AI — pattern reserve → reconcile chống race
 * (spec/AICEM_Technical_Specification.md mục 13.1). Nếu Organization không đặt
 * ai_monthly_budget_usd (null) thì bỏ qua toàn bộ, không tạo dòng đếm.
 */
class CheckAndReserveBudgetAction
{
    use AsAction;

    /** @param array<int, array{role: string, content: string}> $messages @return float|null estimated_cost_usd đã reserve, null nếu org không giới hạn */
    public function handle(AicemGenerationRun $run, array $messages, int $maxTokens): ?float
    {
        $organization = TenantContext::resolve();

        if ($organization->ai_monthly_budget_usd === null) {
            return null;
        }

        $promptChars          = array_sum(array_map(fn (array $m) => mb_strlen($m['content']), $messages));
        $estimatedInputTokens = (int) ceil($promptChars / 4);
        $estimated            = CostCalculator::calculate($run->provider, $run->model, $estimatedInputTokens, $maxTokens);

        $yearMonth = now()->format('Y-m');

        // insertOrIgnore — nhiều job đồng thời cùng org/tháng có thể cùng thấy "chưa có dòng đếm",
        // unique(organization_id, year_month) đảm bảo chỉ 1 insert thành công, các lần khác bị bỏ
        // qua an toàn (không throw), rồi lockForUpdate bên dưới mới thật sự tuần tự hoá việc kiểm tra.
        // Dùng withTrashed() khi tìm lại — model này có SoftDeletes (kế thừa TenantAwareModel) nên
        // 1 hàng đã bị soft-delete (không nên xảy ra trong vận hành bình thường, phòng vệ) vẫn còn
        // chiếm unique key khiến insertOrIgnore bị bỏ qua trong khi query có scope mặc định lại
        // không thấy hàng đó — phải restore() nếu rơi vào trường hợp này.
        DB::table('aicem_monthly_budget_usage')->insertOrIgnore([
            'organization_id' => $organization->id,
            'year_month'      => $yearMonth,
            'reserved_usd'    => 0,
            'settled_usd'     => 0,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        DB::transaction(function () use ($organization, $yearMonth, $estimated) {
            /** @var AicemMonthlyBudgetUsage $usage */
            $usage = AicemMonthlyBudgetUsage::withTrashed()
                ->where('year_month', $yearMonth)
                ->lockForUpdate()
                ->firstOrFail();

            if ($usage->trashed()) {
                $usage->restore();
            }

            $usedSoFar = (float) $usage->reserved_usd + (float) $usage->settled_usd;

            if ($usedSoFar + $estimated > (float) $organization->ai_monthly_budget_usd) {
                throw new BudgetExceededException(sprintf(
                    'Đã vượt hạn mức chi phí AI tháng %s ($%.4f đã dùng/reserve + $%.4f ước tính cho lần chạy này > hạn mức $%.2f/tháng).',
                    $yearMonth, $usedSoFar, $estimated, (float) $organization->ai_monthly_budget_usd
                ));
            }

            $usage->increment('reserved_usd', $estimated);
        });

        return $estimated;
    }
}

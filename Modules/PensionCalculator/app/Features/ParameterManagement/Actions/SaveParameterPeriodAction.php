<?php

namespace Modules\PensionCalculator\Features\ParameterManagement\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\PensionCalculator\Enums\SupportGroupKey;
use Modules\PensionCalculator\Models\PensionParameterPeriod;
use Modules\PensionCalculator\Models\PensionSupportTier;

/**
 * spec/bhxh/PensionCalculator_Technical_Specification.md §9.1/§9.2 — chỉ THÊM giai đoạn hiệu
 * lực mới, KHÔNG sửa/xoá giai đoạn đã tồn tại (bất biến, giống snapshot Assessment). Controller
 * đã validate kiểu dữ liệu cơ bản (Http\PensionParameterAdminController::storePeriod) — Action
 * này chịu trách nhiệm quy tắc nghiệp vụ (§9.2): effective_from phải sau giai đoạn gần nhất.
 */
class SaveParameterPeriodAction
{
    use AsAction;

    /**
     * @param  array{effective_from:string, rural_poverty_line:float, reference_level:float,
     *     contribution_rate_percent:float, ceiling_multiplier:int, source_document:string,
     *     notes:?string, created_by:?int, support_tiers: array<string,float>} $data
     * @return array{period: PensionParameterPeriod, warnings: array<int,string>}
     */
    public function handle(array $data): array
    {
        $latest = PensionParameterPeriod::orderByDesc('effective_from')->first();

        if ($latest && $data['effective_from'] <= $latest->effective_from->format('Y-m-d')) {
            throw ValidationException::withMessages([
                'effective_from' => "Giai đoạn hiệu lực mới phải sau giai đoạn gần nhất hiện có ({$latest->effective_from->format('d/m/Y')}).",
            ]);
        }

        $warnings = $this->buildTierWarnings($data['support_tiers']);

        $period = DB::transaction(function () use ($data) {
            $period = PensionParameterPeriod::create([
                'effective_from' => $data['effective_from'],
                'rural_poverty_line' => $data['rural_poverty_line'],
                'reference_level' => $data['reference_level'],
                'contribution_rate_percent' => $data['contribution_rate_percent'],
                'ceiling_multiplier' => $data['ceiling_multiplier'],
                'source_document' => $data['source_document'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            foreach ($data['support_tiers'] as $groupKey => $supportPercent) {
                PensionSupportTier::create([
                    'period_id' => $period->id,
                    'group_key' => $groupKey,
                    'support_percent' => $supportPercent,
                ]);
            }

            return $period;
        });

        return ['period' => $period->load('supportTiers'), 'warnings' => $warnings];
    }

    /**
     * §9.2 — cảnh báo (không chặn) nếu nhóm "khác" > nhóm "nghèo" (khả năng gõ nhầm).
     *
     * @param  array<string,float>  $tiers
     * @return array<int,string>
     */
    private function buildTierWarnings(array $tiers): array
    {
        $warnings = [];

        $poor = $tiers[SupportGroupKey::PoorHousehold->value] ?? null;
        $other = $tiers[SupportGroupKey::Other->value] ?? null;

        if ($poor !== null && $other !== null && (float) $other > (float) $poor) {
            $warnings[] = 'Tỷ lệ hỗ trợ nhóm "Người tham gia khác" đang CAO HƠN nhóm "Hộ nghèo" — kiểm tra lại có gõ nhầm không.';
        }

        return $warnings;
    }
}

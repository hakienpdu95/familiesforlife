<?php

namespace Modules\Assessment\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Assessment\Models\WorkforceProfile;
use Modules\KpiGoal\Models\AiImpactSnapshot;

/**
 * Digital Workforce Maturity Index (DWMI)
 *
 * Formula:
 *   DWMI = TDWCF Score × AI Adoption Rate × Process Digitalization Rate / 10000
 *
 * Trong đó:
 *   AI Adoption Rate             = avg improvement_pct WHERE impact_category = 'ai_adoption'
 *   Process Digitalization Rate  = avg improvement_pct WHERE impact_type     = 'process_digitalization'
 *
 * Phân loại kết quả:
 *   0–20  : Khởi đầu
 *   21–40 : Nhận thức
 *   41–60 : Thực hành
 *   61–80 : Chuyên nghiệp
 *   81–100: Dẫn dắt chuyển đổi
 */
class CalculateDwmiAction
{
    use AsAction;

    public function handle(WorkforceProfile $profile): ?float
    {
        $tdwcfScore = $profile->tdwcf_score;

        if ($tdwcfScore === null || $profile->employee_id === null) {
            return null;
        }

        $snapshots = AiImpactSnapshot::withoutTenant()
            ->where('employee_id', $profile->employee_id)
            ->get(['impact_category', 'impact_type', 'improvement_pct']);

        $aiAdoptionRate          = $snapshots->where('impact_category', 'ai_adoption')->avg('improvement_pct') ?? 0.0;
        $processDigitalization   = $snapshots->where('impact_type', 'process_digitalization')->avg('improvement_pct') ?? 0.0;

        // Clamp to 0–100 range
        $aiAdoptionRate        = min(100, max(0, $aiAdoptionRate));
        $processDigitalization = min(100, max(0, $processDigitalization));

        $dwmi = round($tdwcfScore * $aiAdoptionRate * $processDigitalization / 10000, 2);

        return min(100, $dwmi);
    }

    public static function classify(float $dwmi): string
    {
        return match (true) {
            $dwmi <= 20 => 'Khởi đầu',
            $dwmi <= 40 => 'Nhận thức',
            $dwmi <= 60 => 'Thực hành',
            $dwmi <= 80 => 'Chuyên nghiệp',
            default     => 'Dẫn dắt chuyển đổi',
        };
    }
}

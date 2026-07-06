<?php

namespace Modules\OcopRubric\Features\ScoringSession\Services;

use Modules\OcopRubric\Models\OcopRubricVersion;
use Modules\OcopRubric\Models\OcopStarBand;

/**
 * Thuần logic tính điểm — không query DB ngoài dữ liệu đã load (caller phải
 * eager-load `sections.criteria` trước), dễ unit test.
 *
 * Thuật toán: KHÔNG chuẩn hoá (khác SectionedAggregation của Assessment) —
 * cộng thẳng điểm option đã chọn, vì thang điểm mỗi Mục đã được luật định sẵn
 * theo đúng tỷ trọng (Phần A=40/B=25/C=35), cộng thẳng là đúng theo Điều 3.
 */
class ScoringCalculator
{
    /** @param array<int,float> $pointsByCriterionId criterion_id => points_awarded (chỉ tiêu chí is_scorable) */
    public function calculate(OcopRubricVersion $version, array $pointsByCriterionId): CalculationResult
    {
        $sectionScores = [];

        foreach ($version->sections as $section) {
            $leafIds = $section->criteria->where('is_scorable', true)->pluck('id');
            $sectionScores[$section->code] = round(
                collect($leafIds)->sum(fn ($id) => $pointsByCriterionId[$id] ?? 0.0),
                2
            );
        }

        $total = round(array_sum($sectionScores), 2);

        $band = OcopStarBand::where('legal_version', 'QD26-2026')
            ->where('min_score', '<=', $total)
            ->where('max_score', '>=', $total)
            ->orderByDesc('star_rank') // biên giới trùng ưu tiên hạng cao hơn theo tinh thần Điều 3.3
            ->first();

        return new CalculationResult(
            sectionScores: $sectionScores,
            totalScore: $total,
            starRank: $band?->star_rank,
            isCertifiable: $band?->is_certifiable ?? false,
        );
    }
}

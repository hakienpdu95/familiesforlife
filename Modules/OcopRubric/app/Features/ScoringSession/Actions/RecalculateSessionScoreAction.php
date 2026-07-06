<?php

namespace Modules\OcopRubric\Features\ScoringSession\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopRubric\Features\ScoringSession\Services\ScoringCalculator;
use Modules\OcopRubric\Models\OcopScoringSession;

/**
 * Tách riêng khỏi AnswerCriterionAction để DuplicateScoringSessionAction dùng
 * lại được — không lặp lại logic tính điểm ở 2 nơi.
 */
class RecalculateSessionScoreAction
{
    use AsAction;

    public function __construct(private readonly ScoringCalculator $calculator) {}

    public function handle(OcopScoringSession $session): OcopScoringSession
    {
        // lockForUpdate: nếu 2 request answer() tới gần như đồng thời (2 tab, hoặc
        // double-click nhanh trên deck), request thứ 2 phải đợi request thứ 1 ghi
        // xong rồi mới đọc lại answers + ghi điểm — tránh "lost update".
        return DB::transaction(function () use ($session) {
            $locked = OcopScoringSession::whereKey($session->id)->lockForUpdate()->firstOrFail();

            $version = $locked->rubricVersion()->with('sections.criteria')->first();
            $pointsMap = $locked->answers()->pluck('points_awarded', 'criterion_id')->all();
            $result = $this->calculator->calculate($version, $pointsMap);

            $locked->update([
                'score_section_a'   => $result->sectionScores['A'] ?? 0,
                'score_section_b'   => $result->sectionScores['B'] ?? 0,
                'score_section_c'   => $result->sectionScores['C'] ?? 0,
                'total_score'       => $result->totalScore,
                'star_rank'         => $result->starRank,
                'criteria_answered' => $locked->answers()->count(),
            ]);

            return $locked->fresh();
        });
    }
}

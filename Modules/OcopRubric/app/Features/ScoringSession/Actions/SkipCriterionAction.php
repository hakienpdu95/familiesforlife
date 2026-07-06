<?php

namespace Modules\OcopRubric\Features\ScoringSession\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopRubric\Enums\ScoringSessionStatus;
use Modules\OcopRubric\Models\OcopRubricCriterion;
use Modules\OcopRubric\Models\OcopScoringAnswer;
use Modules\OcopRubric\Models\OcopScoringSession;

/** Bỏ qua 1 tiêu chí — không tính điểm (0đ), chỉ đánh dấu đã xem. Cùng guard AnswerCriterionAction. */
class SkipCriterionAction
{
    use AsAction;

    public function __construct(private readonly RecalculateSessionScoreAction $recalculate) {}

    public function handle(OcopScoringSession $session, int $criterionId): OcopScoringSession
    {
        if ($session->is_locked || $session->status !== ScoringSessionStatus::InProgress->value) {
            throw new \DomainException('Phiên chấm điểm này đã hoàn thành và bị khoá — không thể sửa câu trả lời.');
        }

        return DB::transaction(function () use ($session, $criterionId) {
            $criterion = OcopRubricCriterion::findOrFail($criterionId);
            if (!$criterion->is_scorable) {
                throw new \DomainException("Tiêu chí '{$criterion->code}' là Mục tổng hợp, không nhận câu trả lời trực tiếp.");
            }

            OcopScoringAnswer::updateOrCreate(
                ['session_id' => $session->id, 'criterion_id' => $criterionId],
                ['option_id' => null, 'points_awarded' => 0, 'needs_review' => false, 'evidence_note' => null, 'answered_at' => now()]
            );

            return $this->recalculate->handle($session);
        });
    }
}

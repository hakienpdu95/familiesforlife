<?php

namespace Modules\OcopRubric\Features\ScoringSession\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopRubric\Enums\ScoringSessionStatus;
use Modules\OcopRubric\Models\OcopScoringDisqualifierFlag;
use Modules\OcopRubric\Models\OcopScoringSession;

/** Tự đánh dấu rủi ro loại hồ sơ (advisory) — cùng guard AnswerCriterionAction. */
class FlagDisqualifierAction
{
    use AsAction;

    public function handle(OcopScoringSession $session, int $disqualifierId, bool $isFlagged): OcopScoringDisqualifierFlag
    {
        if ($session->is_locked || $session->status !== ScoringSessionStatus::InProgress->value) {
            throw new \DomainException('Phiên chấm điểm này đã hoàn thành và bị khoá — không thể sửa.');
        }

        return OcopScoringDisqualifierFlag::updateOrCreate(
            ['session_id' => $session->id, 'disqualifier_id' => $disqualifierId],
            ['is_flagged' => $isFlagged]
        );
    }
}

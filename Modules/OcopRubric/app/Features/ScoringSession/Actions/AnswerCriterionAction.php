<?php

namespace Modules\OcopRubric\Features\ScoringSession\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopRubric\Enums\ScoringSessionStatus;
use Modules\OcopRubric\Features\ScoringSession\Data\AnswerCriterionData;
use Modules\OcopRubric\Models\OcopRubricCriterion;
use Modules\OcopRubric\Models\OcopRubricOption;
use Modules\OcopRubric\Models\OcopScoringAnswer;
use Modules\OcopRubric\Models\OcopScoringSession;

class AnswerCriterionAction
{
    use AsAction;

    public function __construct(private readonly RecalculateSessionScoreAction $recalculate) {}

    public function handle(OcopScoringSession $session, AnswerCriterionData $data): OcopScoringSession
    {
        // Bất biến sau khi hoàn thành — DN không thể sửa lại điểm của 1 phiên đã chấm
        // xong, kể cả gọi thẳng endpoint (guard ở tầng Action, không chỉ ẩn nút UI).
        if ($session->is_locked || $session->status !== ScoringSessionStatus::InProgress->value) {
            throw new \DomainException(
                'Phiên chấm điểm này đã hoàn thành và bị khoá — không thể sửa câu trả lời. '
                . 'Dùng chức năng "Nhân bản" để tạo phiên mới nếu muốn chấm lại hoặc chấm cho sản phẩm khác.'
            );
        }

        return DB::transaction(function () use ($session, $data) {
            // Chỉ tiêu chí lá (is_scorable=true) mới được phép có câu trả lời.
            $criterion = OcopRubricCriterion::findOrFail($data->criterion_id);
            if (!$criterion->is_scorable) {
                throw new \DomainException("Tiêu chí '{$criterion->code}' là Mục tổng hợp, không nhận câu trả lời trực tiếp.");
            }

            // KHÔNG bao giờ nhận points_awarded từ client — điểm luôn tra lại từ
            // chính bảng option do hệ thống sở hữu, request chỉ được chọn ID.
            $option = $data->option_id
                ? OcopRubricOption::where('criterion_id', $data->criterion_id)->findOrFail($data->option_id)
                : null;

            OcopScoringAnswer::updateOrCreate(
                ['session_id' => $session->id, 'criterion_id' => $data->criterion_id],
                [
                    'option_id'      => $option?->id,
                    'points_awarded' => $option?->points ?? 0,
                    'needs_review'   => false,
                    'evidence_note'  => $data->evidence_note,
                    'answered_at'    => now(),
                ]
            );

            return $this->recalculate->handle($session);
        });
    }
}

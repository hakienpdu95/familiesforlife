<?php

namespace Modules\OcopRubric\Features\ScoringSession\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopRubric\Enums\ScoringSessionStatus;
use Modules\OcopRubric\Features\ScoringSession\Events\ScoringSessionDuplicated;
use Modules\OcopRubric\Models\OcopProduct;
use Modules\OcopRubric\Models\OcopScoringAnswer;
use Modules\OcopRubric\Models\OcopScoringSession;

/**
 * Nhân bản 1 phiên đã hoàn thành sang sản phẩm khác (spec §8.4). Phase 4 chỉ
 * xử lý Trường hợp 1 (cùng rubric_version — copy thẳng) và Trường hợp 3 (khác
 * Bộ sản phẩm — để trống). Trường hợp 2 (cùng Bộ sản phẩm nhưng khác version)
 * tạm throw — cần CrossVersionAnswerMapper (Phase 4b), tần suất gần như bằng 0
 * cho tới khi có Bộ sản phẩm nào thật sự publish version 2.
 */
class DuplicateScoringSessionAction
{
    use AsAction;

    public function __construct(private readonly RecalculateSessionScoreAction $recalculate) {}

    public function handle(OcopScoringSession $source, OcopProduct $targetProduct, string $mode): OcopScoringSession
    {
        if ($source->status !== ScoringSessionStatus::Completed->value) {
            throw new \DomainException('Chỉ được nhân bản từ 1 phiên đã hoàn thành.');
        }

        // Phòng vệ chiều sâu — route/policy đã chặn trước, nhưng Action không tin
        // tưởng lớp trên.
        if ($targetProduct->organization_id !== $source->organization_id) {
            throw new \DomainException('Không thể nhân bản sang sản phẩm của tổ chức khác.');
        }

        $sourceGroupId = $source->rubricVersion->product_group_id;
        $sameGroup = $targetProduct->product_group_id === $sourceGroupId;
        $targetRubricVersion = $targetProduct->activeRubricVersion();

        if (!$targetRubricVersion) {
            throw new \DomainException('Bộ tiêu chí cho sản phẩm đích chưa được cấu hình — vui lòng liên hệ quản trị hệ thống.');
        }

        $exactSameVersion = $sameGroup && $targetRubricVersion->id === $source->rubric_version_id;

        if ($sameGroup && !$exactSameVersion) {
            throw new \DomainException(
                'Bộ tiêu chí của sản phẩm đích đã có phiên bản mới hơn — nhân bản chéo '
                . 'phiên bản chưa được hỗ trợ ở phiên bản hiện tại, vui lòng chấm lại thủ công.'
            );
        }

        return DB::transaction(function () use ($source, $targetProduct, $mode, $exactSameVersion, $sameGroup, $targetRubricVersion) {
            $newSession = OcopScoringSession::create([
                'ocop_product_id'            => $targetProduct->id,
                'rubric_version_id'          => $targetRubricVersion->id,
                'duplicated_from_session_id' => $source->id,
                'user_id'                    => auth()->id(),
                'employee_id'                => $source->employee_id,
                'mode'                       => $mode,
                'status'                     => ScoringSessionStatus::InProgress->value,
            ]);

            if ($exactSameVersion) {
                // Trường hợp 1 (§8.4.1) — copy y nguyên khoá ngoại, không cần review
                foreach ($source->answers as $answer) {
                    OcopScoringAnswer::create([
                        'session_id'     => $newSession->id,
                        'criterion_id'   => $answer->criterion_id,
                        'option_id'      => $answer->option_id,
                        'points_awarded' => $answer->points_awarded,
                        'needs_review'   => false,
                        'evidence_note'  => $answer->evidence_note,
                        'answered_at'    => now(),
                    ]);
                }
            }
            // else: Trường hợp 3 (§8.4.3) — khác Bộ sản phẩm, cố tình để trống
            // (Trường hợp 2 đã bị chặn ở guard phía trên trước khi vào transaction)

            $this->recalculate->handle($newSession);

            ScoringSessionDuplicated::dispatch($source, $newSession, $exactSameVersion, $sameGroup);

            return $newSession->fresh('answers');
        });
    }
}

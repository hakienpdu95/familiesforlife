<?php

namespace Modules\OcopRubric\Features\ScoringSession\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopRubric\Enums\ScoringSessionStatus;
use Modules\OcopRubric\Models\OcopScoringSession;

/**
 * Cho phép DN chủ động "bỏ dở" 1 phiên đang thực hiện thay vì để treo mãi ở
 * in_progress — lối thoát tường minh cho case StartScoringSessionAction resume
 * nhầm phiên cũ mà người dùng thật ra muốn làm lại từ đầu.
 */
class AbandonSessionAction
{
    use AsAction;

    public function handle(OcopScoringSession $session): OcopScoringSession
    {
        if ($session->status !== ScoringSessionStatus::InProgress->value) {
            throw new \DomainException('Chỉ có thể bỏ dở phiên đang thực hiện.');
        }

        $session->update([
            'status'       => ScoringSessionStatus::Abandoned->value,
            'is_locked'    => true,
            'completed_at' => now(),
        ]);

        return $session->fresh();
    }
}

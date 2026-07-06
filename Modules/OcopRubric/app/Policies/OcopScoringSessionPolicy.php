<?php

namespace Modules\OcopRubric\Policies;

use App\Models\User;
use App\Shared\Tenancy\TenantContext;
use Modules\OcopRubric\Enums\ScoringSessionStatus;
use Modules\OcopRubric\Models\OcopScoringSession;

class OcopScoringSessionPolicy
{
    /**
     * Xem lịch sử: mọi thành viên CÙNG tổ chức có quyền luyện tập đều xem
     * được, không giới hạn "chỉ xem session của chính mình" — luyện tập là
     * hoạt động tập thể của HTX.
     */
    public function view(User $user, OcopScoringSession $session): bool
    {
        return TenantContext::getOrganizationId() === $session->organization_id
            && ($user->can('ocop_practice.use') || $user->can('ocop_self_assess.use'));
    }

    /**
     * Trả lời/sửa: chỉ khi phiên còn in_progress — is_locked là nguồn sự thật
     * cuối cùng, không suy luận qua status để tránh 2 nơi định nghĩa "đã khoá
     * chưa" lệch nhau.
     */
    public function answer(User $user, OcopScoringSession $session): bool
    {
        return $this->view($user, $session)
            && !$session->is_locked
            && $session->status === ScoringSessionStatus::InProgress->value;
    }

    /** Nhân bản: chỉ từ phiên đã hoàn thành thật sự (không phải abandoned). */
    public function duplicate(User $user, OcopScoringSession $session): bool
    {
        return $this->view($user, $session)
            && $session->status === ScoringSessionStatus::Completed->value;
    }
}

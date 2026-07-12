<?php

namespace Modules\Approval\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Modules\Approval\Enums\ApprovalStatus;

/**
 * spec/Workflow_Approval_Technical_Specification.md §10.
 *
 * Class notification CHUNG — recipient do module tiêu thụ tự quyết định (domain logic riêng
 * của từng entity, giống cách TakeDownArticleTranslationAction của Modules/Post tự chọn
 * recipient bằng User::role(['ceo','ai_operator'])->get()). Action generic trong Approval
 * (SubmitForApprovalAction…) KHÔNG tự gửi notification này — module tiêu thụ tự gọi
 * Notification::send(...) sau khi transition thành công (xem ví dụ ở §10, Modules/Product
 * Phase 4).
 */
class ApprovalStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RespectsNotificationPreferences;

    public function __construct(
        private readonly Model $subject,
        private readonly ApprovalStatus $to,
        private readonly string $subjectLabel,
        private readonly string $url,
    ) {}

    protected function notificationType(): string
    {
        return 'approval_status_changed';
    }

    public function toDatabase(object $notifiable): array
    {
        return NotificationData::make(
            type:     'approval_status_changed',
            title:    "{$this->subjectLabel} đã chuyển sang \"{$this->to->label()}\"",
            body:     'Trạng thái phê duyệt vừa được cập nhật.',
            url:      $this->url,
            icon:     'task',
            severity: $this->to === ApprovalStatus::Archived ? 'info' : 'success',
        );
    }
}

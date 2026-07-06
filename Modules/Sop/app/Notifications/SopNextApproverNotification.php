<?php

namespace Modules\Sop\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Sop\Models\SopApprovalFlow;
use Modules\Sop\Models\SopProcess;
use Modules\Sop\Models\SopVersion;

/**
 * Gửi cho approver bước tiếp theo khi bước trước đã được approved.
 */
class SopNextApproverNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        private readonly SopProcess      $sop,
        private readonly SopVersion      $version,
        private readonly SopApprovalFlow $nextFlow,
    ) {}

    protected function notificationType(): string { return 'sop_next_approver'; }

    public function toDatabase(object $notifiable): array
    {
        return NotificationData::make(
            type:     'sop_next_approver',
            title:    "SOP [{$this->sop->code}] chờ duyệt bước {$this->nextFlow->step_order}",
            body:     "SOP \"{$this->sop->title}\" v{$this->version->version_number} đang chờ bạn duyệt ở bước {$this->nextFlow->step_order}.",
            url:      route('backend.sop.versions.review', [
                'sop'     => $this->sop->uuid,
                'version' => $this->version->uuid,
            ]),
            icon:     'sop',
            severity: 'info',
            meta:     [
                'sop_id'         => $this->sop->id,
                'sop_uuid'       => $this->sop->uuid,
                'version_number' => $this->version->version_number,
                'step_order'     => $this->nextFlow->step_order,
                'required_role'  => $this->nextFlow->required_role,
            ],
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        $reviewUrl = route('backend.sop.versions.review', [
            'sop'     => $this->sop->uuid,
            'version' => $this->version->uuid,
        ]);

        return (new MailMessage)
            ->subject("SOP cần duyệt bước {$this->nextFlow->step_order}: [{$this->sop->code}] {$this->sop->title}")
            ->greeting("Xin chào {$notifiable->name},")
            ->line("Bước duyệt trước của SOP **[{$this->sop->code}] {$this->sop->title}** v{$this->version->version_number} đã hoàn thành.")
            ->line("Hiện tại đang chờ bạn duyệt ở **bước {$this->nextFlow->step_order}**" . ($this->nextFlow->required_role ? " (yêu cầu role: {$this->nextFlow->required_role})" : '') . '.')
            ->action('Xem và duyệt SOP', $reviewUrl)
            ->line('Vui lòng xem xét và thực hiện hành động duyệt / từ chối trong hệ thống.');
    }
}

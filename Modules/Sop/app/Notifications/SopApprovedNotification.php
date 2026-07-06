<?php

namespace Modules\Sop\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Sop\Models\SopProcess;
use Modules\Sop\Models\SopVersion;

/**
 * Gửi cho creator khi version được approve hoàn toàn (tất cả bước đã duyệt xong).
 */
class SopApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        private readonly SopProcess $sop,
        private readonly SopVersion $version,
    ) {}

    protected function notificationType(): string { return 'sop_approved'; }

    public function toDatabase(object $notifiable): array
    {
        return NotificationData::make(
            type:     'sop_approved',
            title:    "SOP [{$this->sop->code}] đã được duyệt",
            body:     "SOP \"{$this->sop->title}\" v{$this->version->version_number} đã được duyệt thành công và có hiệu lực.",
            url:      route('backend.sop.show', $this->sop->uuid),
            icon:     'sop',
            severity: 'success',
            meta:     [
                'sop_id'         => $this->sop->id,
                'sop_uuid'       => $this->sop->uuid,
                'version_number' => $this->version->version_number,
            ],
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        $sopUrl = route('backend.sop.show', $this->sop->uuid);

        return (new MailMessage)
            ->subject("SOP đã được duyệt: [{$this->sop->code}] {$this->sop->title}")
            ->greeting("Xin chào {$notifiable->name},")
            ->line("SOP **[{$this->sop->code}] {$this->sop->title}** phiên bản v{$this->version->version_number} đã được duyệt thành công.")
            ->line('SOP hiện đang có hiệu lực và có thể được sử dụng trong hệ thống.')
            ->action('Xem SOP', $sopUrl);
    }
}

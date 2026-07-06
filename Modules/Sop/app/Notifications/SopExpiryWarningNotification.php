<?php

namespace Modules\Sop\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Sop\Models\SopProcess;

/**
 * Gửi cho owner khi SOP sắp hết hạn trong 7 ngày.
 */
class SopExpiryWarningNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        private readonly SopProcess $sop,
        private readonly int        $daysLeft,
    ) {}

    protected function notificationType(): string { return 'sop_expiry_warning'; }

    public function toDatabase(object $notifiable): array
    {
        return NotificationData::make(
            type:     'sop_expiry_warning',
            title:    "SOP [{$this->sop->code}] sắp hết hạn ({$this->daysLeft} ngày)",
            body:     "SOP \"{$this->sop->title}\" sẽ hết hạn vào {$this->sop->expired_date?->format('d/m/Y')}. Vui lòng gia hạn hoặc thay thế.",
            url:      route('backend.sop.show', $this->sop->uuid),
            icon:     'warning',
            severity: 'warning',
            meta:     [
                'sop_id'       => $this->sop->id,
                'sop_uuid'     => $this->sop->uuid,
                'expired_date' => $this->sop->expired_date?->toDateString(),
                'days_left'    => $this->daysLeft,
            ],
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        $sopUrl = route('backend.sop.show', $this->sop->uuid);

        return (new MailMessage)
            ->subject("SOP sắp hết hạn: [{$this->sop->code}] {$this->sop->title}")
            ->greeting("Xin chào {$notifiable->name},")
            ->line("SOP **[{$this->sop->code}] {$this->sop->title}** sẽ hết hạn vào ngày **{$this->sop->expired_date?->format('d/m/Y')}** ({$this->daysLeft} ngày nữa).")
            ->line('Vui lòng xem xét và gia hạn hoặc thay thế SOP trước khi hết hạn.')
            ->action('Xem SOP', $sopUrl);
    }
}

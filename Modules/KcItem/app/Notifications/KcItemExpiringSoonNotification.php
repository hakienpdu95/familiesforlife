<?php

namespace Modules\KcItem\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\KcItem\Models\KcItem;

class KcItemExpiringSoonNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        private readonly KcItem $kcItem,
    ) {}

    protected function notificationType(): string { return 'kc_expiring_soon'; }

    public function toDatabase(object $notifiable): array
    {
        return NotificationData::make(
            type:     'kc_expiring_soon',
            title:    "Tài liệu \"{$this->kcItem->title}\" sắp hết hiệu lực",
            body:     "Tài liệu \"{$this->kcItem->title}\" sẽ hết hiệu lực vào {$this->kcItem->expired_date?->format('d/m/Y')}.",
            url:      route('backend.kc-items.show', $this->kcItem->id),
            icon:     'warning',
            severity: 'warning',
            meta:     [
                'item_id'      => $this->kcItem->id,
                'expired_date' => $this->kcItem->expired_date?->toDateString(),
            ],
        );
    }
}

<?php

namespace Modules\KcItem\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\KcItem\Models\KcItem;

class KcItemRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        private readonly KcItem  $kcItem,
        private readonly string  $reason,
    ) {}

    protected function notificationType(): string { return 'kc_rejected'; }

    public function toDatabase(object $notifiable): array
    {
        return NotificationData::make(
            type:     'kc_rejected',
            title:    "Tài liệu \"{$this->kcItem->title}\" bị từ chối",
            body:     "Tài liệu \"{$this->kcItem->title}\" đã bị từ chối. Lý do: {$this->reason}",
            url:      route('backend.kc-items.show', $this->kcItem->id),
            icon:     'warning',
            severity: 'warning',
            meta:     ['item_id' => $this->kcItem->id, 'reason' => $this->reason],
        );
    }
}

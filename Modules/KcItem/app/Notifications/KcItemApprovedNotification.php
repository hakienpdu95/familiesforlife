<?php

namespace Modules\KcItem\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\KcItem\Models\KcItem;

class KcItemApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        private readonly KcItem $kcItem,
    ) {}

    protected function notificationType(): string { return 'kc_approved'; }

    public function toDatabase(object $notifiable): array
    {
        return NotificationData::make(
            type:     'kc_approved',
            title:    "Tài liệu \"{$this->kcItem->title}\" đã được duyệt",
            body:     "Tài liệu \"{$this->kcItem->title}\" vừa được duyệt thành công.",
            url:      route('backend.kc-items.show', $this->kcItem->id),
            icon:     'kc',
            severity: 'success',
            meta:     ['item_id' => $this->kcItem->id],
        );
    }
}

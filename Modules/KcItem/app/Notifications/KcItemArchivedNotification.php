<?php

namespace Modules\KcItem\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\KcItem\Models\KcItem;

class KcItemArchivedNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        private readonly KcItem $kcItem,
    ) {}

    protected function notificationType(): string { return 'kc_archived'; }

    public function toDatabase(object $notifiable): array
    {
        return NotificationData::make(
            type:     'kc_archived',
            title:    "Tài liệu \"{$this->kcItem->title}\" đã được lưu trữ",
            body:     "Tài liệu \"{$this->kcItem->title}\" đã hết hiệu lực và được lưu trữ tự động.",
            url:      route('backend.kc-items.show', $this->kcItem->id),
            icon:     'kc',
            severity: 'info',
            meta:     ['item_id' => $this->kcItem->id],
        );
    }
}

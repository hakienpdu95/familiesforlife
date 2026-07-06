<?php

namespace Modules\KcItem\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\KcItem\Models\KcItem;

class KcItemSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(private readonly KcItem $kcItem) {}

    protected function notificationType(): string { return 'kc_submitted'; }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $data = $this->toDatabase($notifiable);
        $data['notification_type'] = $data['type'];
        unset($data['type']);
        return new BroadcastMessage($data);
    }

    public function toWebPush(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function toDatabase(object $notifiable): array
    {
        $submitter = $this->kcItem->owner?->name ?? '—';

        return NotificationData::make(
            type:     'kc_submitted',
            title:    "Tài liệu KC chờ duyệt: {$this->kcItem->title}",
            body:     "{$submitter} vừa gửi tài liệu \"{$this->kcItem->title}\" để duyệt.",
            url:      route('backend.kc-items.show', $this->kcItem),
            icon:     'kc',
            severity: 'info',
            meta:     [
                'kc_item_id'   => $this->kcItem->id,
                'kc_item_uuid' => $this->kcItem->uuid,
                'owner_id'     => $this->kcItem->owner_id,
            ],
        );
    }
}

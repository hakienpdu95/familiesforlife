<?php

namespace Modules\Lead\Notifications;

use App\Models\User;
use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\Lead\Models\Lead;

class LeadAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        private readonly Lead $lead,
        private readonly User $assigner,
    ) {}

    protected function notificationType(): string { return 'lead_assigned'; }

    public function toDatabase(object $notifiable): array
    {
        return NotificationData::make(
            type:     'lead_assigned',
            title:    "Lead mới được giao: {$this->lead->title}",
            body:     "{$this->assigner->name} đã giao lead \"{$this->lead->title}\" cho bạn.",
            url:      route('lead.show', $this->lead),
            icon:     'lead',
            severity: 'info',
            meta:     ['lead_id' => $this->lead->id, 'lead_uuid' => $this->lead->uuid],
        );
    }
}

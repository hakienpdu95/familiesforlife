<?php

namespace Modules\Deployment\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\Deployment\Models\DeploymentTarget;

class TargetCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(private readonly DeploymentTarget $target) {}

    protected function notificationType(): string { return 'deployment_target_created'; }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $d = $this->toDatabase($notifiable);
        $d['notification_type'] = $d['type'];
        unset($d['type']);
        return new BroadcastMessage($d);
    }

    public function toWebPush(object $notifiable): array { return $this->toDatabase($notifiable); }

    public function toDatabase(object $notifiable): array
    {
        $orgName = $this->target->targetOrganization?->name ?? "Target #{$this->target->id}";
        return NotificationData::make(
            type:     'deployment_target_created',
            title:    "Target mới được thêm: {$orgName}",
            body:     "Đã thêm {$orgName} vào danh sách triển khai.",
            url:      route('deployment.targets.show', ['vertical' => $this->target->vertical_code, 'target' => $this->target->id]),
            icon:     'plus-circle',
            severity: 'info',
            meta:     ['target_id' => $this->target->id],
        );
    }
}

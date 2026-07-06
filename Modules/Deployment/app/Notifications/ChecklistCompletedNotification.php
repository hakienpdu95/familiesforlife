<?php

namespace Modules\Deployment\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\Deployment\Models\DeploymentTarget;

class ChecklistCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        private readonly DeploymentTarget $target,
        private readonly string           $phase,
        private readonly int              $pct,
    ) {}

    protected function notificationType(): string
    {
        return 'deployment_checklist_completed';
    }

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
        $orgName = $this->target->targetOrganization?->name ?? "Target #{$this->target->id}";

        return NotificationData::make(
            type:     'deployment_checklist_completed',
            title:    "Checklist phase [{$this->phase}] hoàn thành 100%",
            body:     "{$orgName} đã hoàn thành toàn bộ checklist phase [{$this->phase}].",
            url:      route('deployment.targets.show', [
                'vertical' => $this->target->vertical_code,
                'target'   => $this->target->id,
            ]),
            icon:     'check-circle',
            severity: 'success',
            meta:     ['target_id' => $this->target->id, 'phase' => $this->phase],
        );
    }
}

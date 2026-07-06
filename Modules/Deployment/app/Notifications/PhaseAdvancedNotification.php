<?php

namespace Modules\Deployment\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\Deployment\Models\DeploymentTarget;

class PhaseAdvancedNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        private readonly DeploymentTarget $target,
        private readonly string           $fromPhase,
        private readonly string           $toPhase,
    ) {}

    protected function notificationType(): string
    {
        return 'deployment_phase_advanced';
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
            type:     'deployment_phase_advanced',
            title:    "Phase chuyển sang [{$this->toPhase}]",
            body:     "{$orgName} vừa chuyển từ phase [{$this->fromPhase}] sang [{$this->toPhase}].",
            url:      route('deployment.targets.show', [
                'vertical' => $this->target->vertical_code,
                'target'   => $this->target->id,
            ]),
            icon:     'deployment',
            severity: 'info',
            meta:     [
                'target_id'  => $this->target->id,
                'from_phase' => $this->fromPhase,
                'to_phase'   => $this->toPhase,
            ],
        );
    }
}

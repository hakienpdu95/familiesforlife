<?php

namespace Modules\Deployment\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\Deployment\Models\DeploymentTarget;

class ValidatorFoundIssuesNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        private readonly DeploymentTarget $target,
        private readonly int              $count,
        private readonly int              $score,
    ) {}

    protected function notificationType(): string { return 'deployment_validator_issues'; }

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
            type:     'deployment_validator_issues',
            title:    "Validator tìm thấy {$this->count} vấn đề tại {$orgName}",
            body:     "Data quality score: {$this->score}/100. Nhấn để xem chi tiết.",
            url:      route('deployment.issues.index', ['vertical' => $this->target->vertical_code]),
            icon:     'shield-exclamation',
            severity: $this->score < 60 ? 'error' : 'warning',
            meta:     ['target_id' => $this->target->id, 'score' => $this->score],
        );
    }
}

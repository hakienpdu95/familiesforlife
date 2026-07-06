<?php

namespace Modules\Deployment\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\Deployment\Models\DeploymentIssue;

class IssueCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        private readonly DeploymentIssue $issue,
    ) {}

    protected function notificationType(): string
    {
        return 'deployment_issue_created';
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
        $severity = $this->issue->severity instanceof \Modules\Deployment\Enums\IssueSeverity
            ? $this->issue->severity->value
            : (string) $this->issue->severity;

        return NotificationData::make(
            type:     'deployment_issue_created',
            title:    "Issue mới [{$severity}]: {$this->issue->title}",
            body:     $this->issue->description ?? 'Nhấn để xem chi tiết.',
            url:      route('deployment.issues.show', [
                'vertical' => $this->issue->target?->vertical_code ?? '',
                'issue'    => $this->issue->id,
            ]),
            icon:     'alert',
            severity: match ($severity) {
                'critical', 'high' => 'error',
                'medium'           => 'warning',
                default            => 'info',
            },
            meta: ['issue_id' => $this->issue->id, 'severity' => $severity],
        );
    }
}

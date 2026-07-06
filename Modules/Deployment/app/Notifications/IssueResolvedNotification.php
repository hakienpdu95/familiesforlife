<?php

namespace Modules\Deployment\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\Deployment\Models\DeploymentIssue;

class IssueResolvedNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(private readonly DeploymentIssue $issue) {}

    protected function notificationType(): string { return 'deployment_issue_resolved'; }

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
        return NotificationData::make(
            type:     'deployment_issue_resolved',
            title:    "Issue đã giải quyết: {$this->issue->title}",
            body:     "Issue \"{$this->issue->title}\" đã được đóng lại.",
            url:      route('deployment.issues.show', [
                'vertical' => $this->issue->target?->vertical_code ?? '',
                'issue'    => $this->issue->id,
            ]),
            icon:     'check-circle',
            severity: 'success',
            meta:     ['issue_id' => $this->issue->id],
        );
    }
}

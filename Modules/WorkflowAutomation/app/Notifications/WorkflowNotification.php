<?php

namespace Modules\WorkflowAutomation\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        public readonly string $title,
        public readonly string $body,
    ) {}

    protected function notificationType(): string { return 'workflow_notification'; }

    public function toDatabase(object $notifiable): array
    {
        return NotificationData::make(
            type:     'workflow_notification',
            title:    $this->title,
            body:     $this->body,
            url:      '',
            icon:     'bell',
            severity: 'info',
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->body);
    }
}

<?php

namespace Modules\Task\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\Task\Models\Task;
use App\Models\User;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        private readonly Task $task,
        private readonly User $assigner,
    ) {}

    protected function notificationType(): string { return 'task_assigned'; }

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
        return NotificationData::make(
            type:     'task_assigned',
            title:    "Task mới được giao: {$this->task->title}",
            body:     "{$this->assigner->name} đã giao cho bạn task \"{$this->task->title}\".",
            url:      route('backend.tasks.show', $this->task),
            icon:     'task',
            severity: 'info',
            meta:     ['task_id' => $this->task->id, 'task_uuid' => $this->task->uuid],
        );
    }
}

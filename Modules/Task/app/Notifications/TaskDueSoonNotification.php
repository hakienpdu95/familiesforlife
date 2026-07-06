<?php

namespace Modules\Task\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\Task\Models\Task;

class TaskDueSoonNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(private readonly Task $task) {}

    protected function notificationType(): string { return 'task_due_soon'; }

    public function toDatabase(object $notifiable): array
    {
        $due = $this->task->due_date?->format('d/m/Y') ?? '—';

        return NotificationData::make(
            type:     'task_due_soon',
            title:    "Task đến hạn ngày mai: {$this->task->title}",
            body:     "Task \"{$this->task->title}\" sẽ đến hạn vào {$due}. Hãy hoàn thành sớm.",
            url:      route('backend.tasks.show', $this->task),
            icon:     'task',
            severity: 'warning',
            meta:     ['task_id' => $this->task->id, 'due_date' => $this->task->due_date?->toDateString()],
        );
    }
}

<?php

namespace Modules\Task\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\Task\Models\Task;

class TaskOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        private readonly Task $task,
        private readonly bool $isCreator = false,
    ) {}

    protected function notificationType(): string { return 'task_overdue'; }

    public function toDatabase(object $notifiable): array
    {
        $due = $this->task->due_date?->format('d/m/Y') ?? '—';
        $suffix = $this->isCreator
            ? "Task này đã quá hạn ({$due}) và chưa hoàn thành."
            : "Task \"{$this->task->title}\" đã quá hạn ({$due}).";

        return NotificationData::make(
            type:     'task_overdue',
            title:    "Task quá hạn: {$this->task->title}",
            body:     $suffix,
            url:      route('backend.tasks.show', $this->task),
            icon:     'warning',
            severity: 'error',
            meta:     ['task_id' => $this->task->id, 'due_date' => $this->task->due_date?->toDateString()],
        );
    }
}

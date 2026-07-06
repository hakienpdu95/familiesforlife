<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Task\Enums\TaskStatus;
use Modules\Task\Models\Task;
use Modules\Task\Notifications\TaskDueSoonNotification;

class SendTaskDueSoonNotifications extends Command
{
    protected $signature   = 'notifications:task-due-soon';
    protected $description = 'Gửi thông báo cho assignee khi task đến hạn vào ngày mai';

    public function handle(): int
    {
        $tomorrow = now()->addDay()->toDateString();

        $tasks = Task::with(['employee.user'])
            ->whereDate('due_date', $tomorrow)
            ->whereNotIn('status', [TaskStatus::Done->value, TaskStatus::Cancelled->value])
            ->whereHas('employee.user')
            ->get();

        $sent = 0;

        foreach ($tasks as $task) {
            $user = $task->employee?->user;
            if (!$user) continue;

            $user->notify(new TaskDueSoonNotification($task));
            $sent++;
        }

        $this->info("Đã gửi {$sent} thông báo task-due-soon cho ngày {$tomorrow}.");
        Log::info("notifications:task-due-soon sent={$sent} date={$tomorrow}");

        return self::SUCCESS;
    }
}

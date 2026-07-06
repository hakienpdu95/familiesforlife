<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Task\Enums\TaskStatus;
use Modules\Task\Models\Task;
use Modules\Task\Notifications\TaskOverdueNotification;

class SendTaskOverdueNotifications extends Command
{
    protected $signature   = 'notifications:task-overdue';
    protected $description = 'Gửi thông báo cho assignee và creator khi task quá hạn hôm nay';

    public function handle(): int
    {
        $today = now()->toDateString();

        // Tasks that JUST became overdue today (due_date = yesterday)
        $tasks = Task::with(['employee.user', 'creator'])
            ->whereDate('due_date', now()->subDay()->toDateString())
            ->whereNotIn('status', [TaskStatus::Done->value, TaskStatus::Cancelled->value])
            ->get();

        $sent = 0;

        foreach ($tasks as $task) {
            $notified = collect();

            // Notify assignee
            $assigneeUser = $task->employee?->user;
            if ($assigneeUser) {
                $assigneeUser->notify(new TaskOverdueNotification($task, false));
                $notified->push($assigneeUser->id);
                $sent++;
            }

            // Notify creator (if different)
            $creator = $task->creator;
            if ($creator && !$notified->contains($creator->id)) {
                $creator->notify(new TaskOverdueNotification($task, true));
                $sent++;
            }
        }

        $this->info("Đã gửi {$sent} thông báo task-overdue (quá hạn từ hôm qua).");
        Log::info("notifications:task-overdue sent={$sent} date={$today}");

        return self::SUCCESS;
    }
}

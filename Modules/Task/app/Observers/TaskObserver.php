<?php

namespace Modules\Task\Observers;

use Illuminate\Support\Facades\DB;
use Modules\Task\Jobs\UpdateProjectProgressJob;
use Modules\Task\Jobs\UpdateTaskProgressJob;
use Modules\Task\Models\Task;

class TaskObserver
{
    public function updated(Task $task): void
    {
        if (!$task->isDirty('status')) {
            return;
        }

        $newStatus = $task->status;
        $oldStatus = $task->getOriginal('status');

        if ($newStatus->isDone() && $oldStatus !== 'done') {
            $task->saveQuietly(['completed_at' => now()]);
        } elseif (!$newStatus->isDone() && $oldStatus === 'done') {
            $task->saveQuietly(['completed_at' => null]);
        }

        if ($task->parent_id) {
            $doneCnt = DB::table('tasks')
                ->where('parent_id', $task->parent_id)
                ->where('status', 'done')
                ->whereNull('deleted_at')
                ->count();

            DB::table('tasks')->where('id', $task->parent_id)->update(['subtask_done' => $doneCnt]);

            if ($task->is_leaf) {
                UpdateTaskProgressJob::dispatch($task->parent_id);
            }
        }

        UpdateProjectProgressJob::dispatch($task->project_id);
    }
}

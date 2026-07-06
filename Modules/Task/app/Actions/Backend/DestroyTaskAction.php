<?php

namespace Modules\Task\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Task\Jobs\UpdateProjectProgressJob;
use Modules\Task\Jobs\UpdateTaskProgressJob;
use Modules\Task\Models\Task;

class DestroyTaskAction
{
    use AsAction;

    public function handle(Task $task): string
    {
        $title    = $task->title;
        $parentId = $task->parent_id;
        $projectId = $task->project_id;

        if ($parentId) {
            DB::table('tasks')->where('id', $parentId)->decrement('subtask_total');

            if ($task->status->isDone()) {
                DB::table('tasks')->where('id', $parentId)->decrement('subtask_done');
            }

            $remaining = DB::table('tasks')
                ->where('parent_id', $parentId)
                ->where('id', '!=', $task->id)
                ->whereNull('deleted_at')
                ->count();

            if ($remaining === 0) {
                DB::table('tasks')->where('id', $parentId)->update(['is_leaf' => true]);
            }
        }

        $task->delete();

        UpdateProjectProgressJob::dispatch($projectId);

        if ($parentId) {
            UpdateTaskProgressJob::dispatch($parentId);
        }

        return $title;
    }
}

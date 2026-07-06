<?php

namespace Modules\Task\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Task\Models\Task;

class ToggleWatcherAction
{
    use AsAction;

    public function handle(Task $task, int $userId): bool
    {
        $exists = DB::table('task_watchers')
            ->where('task_id', $task->id)
            ->where('user_id', $userId)
            ->exists();

        if ($exists) {
            DB::table('task_watchers')
                ->where('task_id', $task->id)
                ->where('user_id', $userId)
                ->delete();
            return false; // now unwatching
        }

        DB::table('task_watchers')->insert([
            'task_id'    => $task->id,
            'user_id'    => $userId,
            'watched_at' => now(),
        ]);
        return true; // now watching
    }
}

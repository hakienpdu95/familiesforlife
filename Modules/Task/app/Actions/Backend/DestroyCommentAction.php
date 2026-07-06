<?php

namespace Modules\Task\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Task\Models\TaskComment;

class DestroyCommentAction
{
    use AsAction;

    public function handle(TaskComment $comment): void
    {
        $taskId = $comment->task_id;

        $comment->delete(); // soft delete

        DB::table('tasks')->where('id', $taskId)->decrement('comment_count');
    }
}

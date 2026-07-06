<?php

namespace Modules\Task\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Task\Data\Requests\StoreCommentData;
use Modules\Task\Models\Task;
use Modules\Task\Models\TaskComment;
use Modules\Task\Models\TaskCommentMention;

class StoreCommentAction
{
    use AsAction;

    public function handle(Task $task, StoreCommentData $data): TaskComment
    {
        $comment = TaskComment::create([
            'task_id'   => $task->id,
            'user_id'   => auth()->id(),
            'parent_id' => $data->parent_id,
            'content'   => $data->content,
        ]);

        // Increment comment_count atomically
        DB::table('tasks')->where('id', $task->id)->increment('comment_count');

        // Parse @mention pattern: @[Name](userId)
        $this->parseMentions($comment);

        return $comment;
    }

    private function parseMentions(TaskComment $comment): void
    {
        preg_match_all('/@\[.+?\]\((\d+)\)/', $comment->content, $matches);

        $userIds = array_unique(array_map('intval', $matches[1] ?? []));
        if (empty($userIds)) return;

        $now = now();
        $rows = array_map(fn ($uid) => [
            'comment_id' => $comment->id,
            'user_id'    => $uid,
            'created_at' => $now,
        ], $userIds);

        DB::table('task_comment_mentions')->insertOrIgnore($rows);
    }
}

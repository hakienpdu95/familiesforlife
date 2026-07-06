<?php

namespace Modules\Task\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Task\Models\TaskComment;

class UpdateCommentAction
{
    use AsAction;

    public function handle(TaskComment $comment, string $content): TaskComment
    {
        $comment->update([
            'content'   => $content,
            'is_edited' => true,
        ]);

        // Re-parse mentions: delete old, insert new
        DB::table('task_comment_mentions')->where('comment_id', $comment->id)->delete();
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

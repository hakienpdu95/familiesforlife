<?php

namespace Modules\Task\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Task\Actions\Backend\DestroyCommentAction;
use Modules\Task\Actions\Backend\StoreCommentAction;
use Modules\Task\Actions\Backend\UpdateCommentAction;
use Modules\Task\Data\Requests\StoreCommentData;
use Modules\Task\Models\Task;
use Modules\Task\Models\TaskComment;

class TaskCommentController extends Controller
{
    public function store(
        Request $request,
        Task $task,
        StoreCommentAction $action
    ): JsonResponse {
        $this->authorize('view', $task);

        $data = StoreCommentData::validateAndCreate($request->all());
        $comment = $action->handle($task, $data);

        $comment->load('user:id,name');

        return response()->json([
            'comment' => $this->formatComment($comment),
        ], 201);
    }

    public function update(
        Request $request,
        Task $task,
        TaskComment $comment,
        UpdateCommentAction $action
    ): JsonResponse {
        abort_if($comment->task_id !== $task->id, 404);
        $this->authorize('view', $task);

        if (auth()->id() !== $comment->user_id) {
            abort(403, 'Chỉ người viết mới có thể sửa bình luận.');
        }

        $request->validate(['content' => ['required', 'string', 'max:5000']]);
        $comment = $action->handle($comment, $request->input('content'));
        $comment->load('user:id,name');

        return response()->json([
            'comment' => $this->formatComment($comment),
        ]);
    }

    public function destroy(
        Task $task,
        TaskComment $comment,
        DestroyCommentAction $action
    ): JsonResponse {
        abort_if($comment->task_id !== $task->id, 404);
        $this->authorize('view', $task);

        if (auth()->id() !== $comment->user_id) {
            $this->authorize('delete', $task);
        }

        $action->handle($comment);

        return response()->json(['message' => 'Đã xóa bình luận.']);
    }

    private function formatComment(TaskComment $comment): array
    {
        return [
            'id'         => $comment->id,
            'content'    => $comment->content,
            'is_edited'  => $comment->is_edited,
            'parent_id'  => $comment->parent_id,
            'user_name'  => $comment->user?->name,
            'user_id'    => $comment->user_id,
            'created_at' => $comment->created_at?->format('d/m/Y H:i'),
            'updated_at' => $comment->updated_at?->format('d/m/Y H:i'),
        ];
    }
}

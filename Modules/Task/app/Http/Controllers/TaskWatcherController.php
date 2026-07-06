<?php

namespace Modules\Task\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Task\Actions\Backend\ToggleWatcherAction;
use Modules\Task\Models\Task;

class TaskWatcherController extends Controller
{
    public function toggle(Task $task, ToggleWatcherAction $action): JsonResponse
    {
        $this->authorize('view', $task);

        $watching = $action->handle($task, auth()->id());

        return response()->json([
            'watching' => $watching,
            'message'  => $watching ? 'Đang theo dõi.' : 'Đã hủy theo dõi.',
        ]);
    }
}

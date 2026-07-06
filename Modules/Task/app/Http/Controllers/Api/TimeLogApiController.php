<?php

namespace Modules\Task\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Task\Actions\Backend\DestroyTimeLogAction;
use Modules\Task\Actions\Backend\StoreTimeLogAction;
use Modules\Task\Actions\Backend\UpdateTimeLogAction;
use Modules\Task\Data\Requests\StoreTimeLogData;
use Modules\Task\Data\Requests\UpdateTimeLogData;
use Modules\Task\Models\Task;
use Modules\Task\Models\TimeLog;

class TimeLogApiController extends Controller
{
    public function index(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $logs = TimeLog::where('task_id', $task->id)
            ->with('employee:id,full_name')
            ->orderByDesc('log_date')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $logs]);
    }

    public function store(Request $request, Task $task, StoreTimeLogAction $action): JsonResponse
    {
        $this->authorize('update', $task);

        $data = StoreTimeLogData::validateAndCreate(
            array_merge($request->all(), ['task_id' => $task->id])
        );

        $log = $action->handle($data);
        $log->load('employee:id,full_name');

        return response()->json(['data' => $log], 201);
    }

    public function update(Request $request, TimeLog $log, UpdateTimeLogAction $action): JsonResponse
    {
        $this->authorize('update', $log->task);

        $data = UpdateTimeLogData::validateAndCreate($request->all());
        $log  = $action->handle($log, $data);

        return response()->json(['data' => $log]);
    }

    public function destroy(TimeLog $log, DestroyTimeLogAction $action): JsonResponse
    {
        $this->authorize('update', $log->task);

        $action->handle($log);

        return response()->json(['message' => 'Đã xóa bản ghi giờ làm việc.']);
    }
}

<?php

namespace Modules\Task\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Project\Models\Project;
use Modules\Task\Http\Resources\TaskListResource;
use Modules\Task\Models\Task;
use Modules\Task\Queries\ListTasksHandler;
use Modules\Task\Queries\ListTasksQuery;

class TaskApiController extends Controller
{
    public function index(Request $request, ListTasksHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', Task::class);

        $sort      = $request->input('sort', []);
        $sortField = $sort[0]['field'] ?? 'created_at';
        $sortDir   = $sort[0]['dir']   ?? 'desc';

        $query = new ListTasksQuery(
            page:       max(1, $request->integer('page', 1)),
            perPage:    min(100, max(5, $request->integer('size', 25))),
            sortField:  $sortField,
            sortDir:    $sortDir,
            search:     $request->input('search'),
            projectId:  $request->filled('project_id')  ? (int) $request->input('project_id')  : null,
            status:     $request->input('status'),
            priority:   $request->input('priority'),
            taskType:   $request->input('task_type'),
            employeeId: $request->filled('employee_id') ? (int) $request->input('employee_id') : null,
            dateFrom:   $request->input('date_from'),
            dateTo:     $request->input('date_to'),
            isArchived: (bool) $request->input('is_archived', false),
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => TaskListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }

    public function byProject(Request $request, Project $project, ListTasksHandler $handler): JsonResponse
    {
        $this->authorize('view', $project);

        $sort      = $request->input('sort', []);
        $sortField = $sort[0]['field'] ?? 'sort_order';
        $sortDir   = $sort[0]['dir']   ?? 'asc';

        $query = new ListTasksQuery(
            page:       max(1, $request->integer('page', 1)),
            perPage:    min(100, max(5, $request->integer('size', 25))),
            sortField:  $sortField,
            sortDir:    $sortDir,
            search:     $request->input('search'),
            projectId:  $project->id,
            status:     $request->input('status'),
            priority:   $request->input('priority'),
            taskType:   $request->input('task_type'),
            employeeId: $request->filled('employee_id') ? (int) $request->input('employee_id') : null,
            dateFrom:   $request->input('date_from'),
            dateTo:     $request->input('date_to'),
            isArchived: (bool) $request->input('is_archived', false),
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => TaskListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}

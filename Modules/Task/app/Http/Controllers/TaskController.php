<?php

namespace Modules\Task\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Employee\Enums\EmployeeStatus;
use Modules\Employee\Models\Employee;
use Modules\Project\Enums\ProjectStatus;
use Modules\Project\Models\Project;
use Modules\Task\Actions\Backend\DestroyTaskAction;
use Modules\Task\Actions\Backend\StoreTaskAction;
use Modules\Task\Actions\Backend\UpdateTaskAction;
use Modules\Task\Data\Requests\StoreTaskData;
use Modules\Task\Data\Requests\UpdateTaskData;
use Modules\Task\Enums\TaskPriority;
use Modules\Task\Enums\TaskStatus;
use Modules\Task\Enums\TaskType;
use Modules\Task\Models\Task;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Task::class, 'task');
    }

    private function _resolveOrganizations(): array
    {
        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            return [Organization::where('id', $userOrgId)->get(['id', 'name']), $userOrgId, true];
        }
        return [Organization::orderBy('name')->get(['id', 'name']), null, false];
    }

    public function index()
    {
        $orgId = TenantContext::getOrganizationId();

        $counts = Task::withoutTenant()
            ->where('organization_id', $orgId)
            ->whereNull('deleted_at')
            ->where('is_archived', false)
            ->selectRaw("
                COUNT(*) as total_all,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as total_in_progress,
                SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as total_done,
                SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as total_blocked
            ")
            ->first();

        $statuses   = collect(TaskStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        $priorities = collect(TaskPriority::cases())
            ->map(fn ($p) => ['value' => $p->value, 'text' => $p->label()])
            ->all();

        $taskTypes = collect(TaskType::cases())
            ->map(fn ($t) => ['value' => $t->value, 'text' => $t->label()])
            ->all();

        $projects = Project::withoutTenant()
            ->where('organization_id', $orgId)
            ->whereIn('status', [ProjectStatus::Active->value, ProjectStatus::Planning->value])
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn ($p) => ['value' => $p->id, 'text' => $p->name . ' (' . $p->code . ')'])
            ->all();

        $employees = Employee::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', EmployeeStatus::Active->value)
            ->orderBy('full_name')
            ->get(['id', 'full_name'])
            ->map(fn ($e) => ['value' => $e->id, 'text' => $e->full_name])
            ->all();

        return view('task::tasks.index', compact(
            'counts', 'statuses', 'priorities', 'taskTypes', 'projects', 'employees'
        ));
    }

    public function create()
    {
        $orgId = TenantContext::getOrganizationId();

        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();

        // Org-user: load server-side. Super-admin: load via API on org change.
        $projects  = $orgLocked
            ? Project::withoutTenant()
                ->where('organization_id', $orgId)
                ->whereIn('status', [ProjectStatus::Active->value, ProjectStatus::Planning->value])
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
            : collect();

        $employees = $orgLocked
            ? Employee::withoutTenant()
                ->where('organization_id', $orgId)
                ->where('status', EmployeeStatus::Active->value)
                ->orderBy('full_name')
                ->get(['id', 'full_name'])
            : collect();

        $statuses   = collect(TaskStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        $priorities = collect(TaskPriority::cases())
            ->map(fn ($p) => ['value' => $p->value, 'text' => $p->label()])
            ->all();

        $taskTypes = collect(TaskType::cases())
            ->map(fn ($t) => ['value' => $t->value, 'text' => $t->label()])
            ->all();

        return view('task::tasks.create', compact(
            'projects', 'employees', 'statuses', 'priorities', 'taskTypes',
            'organizations', 'defaultOrgId', 'orgLocked'
        ));
    }

    public function store(Request $request, StoreTaskAction $action): RedirectResponse
    {
        $data = StoreTaskData::validateAndCreate($request->all());
        $task = $action->handle($data);

        return redirect()->route('backend.tasks.show', $task)
            ->with('success', 'Công việc "' . $task->title . '" đã được tạo thành công.');
    }

    public function show(Task $task)
    {
        $task->load([
            'project',
            'employee',
            'labels',
            'parent',
            'createdBy',
            'updatedBy',
            'comments.user:id,name',
            'comments.replies.user:id,name',
        ]);

        $isWatching = $task->isWatchedBy(auth()->id());

        return view('task::tasks.show', compact('task', 'isWatching'));
    }

    public function edit(Task $task)
    {
        $orgId = TenantContext::getOrganizationId();

        $projects = Project::withoutTenant()
            ->where('organization_id', $orgId)
            ->whereIn('status', [ProjectStatus::Active->value, ProjectStatus::Planning->value])
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $employees = Employee::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', EmployeeStatus::Active->value)
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        $statuses   = collect(TaskStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        $priorities = collect(TaskPriority::cases())
            ->map(fn ($p) => ['value' => $p->value, 'text' => $p->label()])
            ->all();

        $taskTypes = collect(TaskType::cases())
            ->map(fn ($t) => ['value' => $t->value, 'text' => $t->label()])
            ->all();

        $task->load(['project', 'employee', 'labels']);

        return view('task::tasks.edit', compact(
            'task', 'projects', 'employees', 'statuses', 'priorities', 'taskTypes'
        ));
    }

    public function update(Request $request, Task $task, UpdateTaskAction $action): RedirectResponse
    {
        $data = UpdateTaskData::validateAndCreate($request->all());
        $action->handle($task, $data);

        return redirect()->route('backend.tasks.show', $task)
            ->with('success', 'Cập nhật công việc thành công.');
    }

    public function destroy(Request $request, Task $task, DestroyTaskAction $action): RedirectResponse|JsonResponse
    {
        $title = $action->handle($task);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa công việc "' . $title . '".']);
        }

        return redirect()->route('backend.tasks.index')
            ->with('success', 'Đã xóa công việc "' . $title . '".');
    }
}

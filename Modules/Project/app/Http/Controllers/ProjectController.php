<?php

namespace Modules\Project\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Branch\Models\Branch;
use Modules\Department\Models\Department;
use Modules\Employee\Enums\EmployeeStatus;
use Modules\Employee\Models\Employee;
use Modules\Project\Actions\Backend\DestroyProjectAction;
use Modules\Project\Actions\Backend\StoreProjectAction;
use Modules\Project\Actions\Backend\UpdateProjectAction;
use Modules\Project\Data\Requests\StoreProjectData;
use Modules\Project\Data\Requests\UpdateProjectData;
use Modules\Project\Enums\ProjectPriority;
use Modules\Project\Enums\ProjectStatus;
use Modules\Project\Models\Project;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Project::class, 'project');
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
        $orgId        = TenantContext::getOrganizationId();
        $isSuperAdmin = auth()->user()?->hasRole('super-admin') ?? false;

        $countQuery = Project::withoutTenant()
            ->selectRaw(
                'COUNT(*) as total_all,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_active,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_planning,
                 SUM(CASE WHEN status IN (?,?) THEN 1 ELSE 0 END) as total_done',
                [
                    ProjectStatus::Active->value,
                    ProjectStatus::Planning->value,
                    ProjectStatus::Completed->value,
                    ProjectStatus::Cancelled->value,
                ]
            );

        if (! $isSuperAdmin) {
            $countQuery->where('organization_id', $orgId);
        }

        $counts = $countQuery->first();

        $totalAll      = (int) ($counts->total_all      ?? 0);
        $totalActive   = (int) ($counts->total_active   ?? 0);
        $totalPlanning = (int) ($counts->total_planning ?? 0);
        $totalDone     = (int) ($counts->total_done     ?? 0);

        $statuses   = collect(ProjectStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        $priorities = collect(ProjectPriority::cases())
            ->map(fn ($p) => ['value' => $p->value, 'text' => $p->label()])
            ->all();

        // Branch/dept filters only make sense when scoped to one org
        $branches    = [];
        $departments = [];

        if (! $isSuperAdmin && $orgId) {
            $branches = Branch::withoutTenant()
                ->where('organization_id', $orgId)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(fn ($b) => ['value' => $b->id, 'text' => $b->name . ' (' . $b->code . ')'])
                ->all();

            $departments = Department::withoutTenant()
                ->where('organization_id', $orgId)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(fn ($d) => ['value' => $d->id, 'text' => $d->name . ' (' . $d->code . ')'])
                ->all();
        }

        return view('project::index', compact(
            'totalAll', 'totalActive', 'totalPlanning', 'totalDone',
            'statuses', 'priorities', 'branches', 'departments'
        ));
    }

    public function create()
    {
        $orgId = TenantContext::getOrganizationId();

        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();

        $branches = Branch::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $departments = Department::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $employees = Employee::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', EmployeeStatus::Active->value)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'employee_code']);

        $statuses   = collect(ProjectStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        $priorities = collect(ProjectPriority::cases())
            ->map(fn ($p) => ['value' => $p->value, 'text' => $p->label()])
            ->all();

        return view('project::create', compact(
            'branches', 'departments', 'employees', 'statuses', 'priorities', 'organizations', 'defaultOrgId', 'orgLocked'
        ));
    }

    public function store(Request $request, StoreProjectAction $action): RedirectResponse
    {
        $data    = StoreProjectData::validateAndCreate($request->all());
        $project = $action->handle($data);

        return redirect()->route('backend.projects.show', $project)
            ->with('success', 'Dự án "' . $project->name . '" đã được tạo thành công.');
    }

    public function show(Project $project)
    {
        $project->load([
            'branch', 'department', 'owner.jobTitle',
            'activeMembers.employee.department',
            'createdBy', 'updatedBy',
        ]);

        return view('project::show', compact('project'));
    }

    public function edit(Project $project)
    {
        $orgId = TenantContext::getOrganizationId();

        [$organizations, , $orgLocked] = $this->_resolveOrganizations();

        $branches = Branch::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $departments = Department::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $employees = Employee::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', EmployeeStatus::Active->value)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'employee_code']);

        $statuses   = collect(ProjectStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        $priorities = collect(ProjectPriority::cases())
            ->map(fn ($p) => ['value' => $p->value, 'text' => $p->label()])
            ->all();

        $project->load(['branch', 'department', 'owner']);

        return view('project::edit', compact(
            'project', 'branches', 'departments', 'employees', 'statuses', 'priorities', 'organizations', 'orgLocked'
        ));
    }

    public function update(Request $request, Project $project, UpdateProjectAction $action): RedirectResponse
    {
        $data = UpdateProjectData::validateAndCreate($request->all());
        $action->handle($project, $data);

        return redirect()->route('backend.projects.show', $project)
            ->with('success', 'Cập nhật dự án thành công.');
    }

    public function destroy(Request $request, Project $project, DestroyProjectAction $action): RedirectResponse|JsonResponse
    {
        $name = $action->handle($project);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa dự án "' . $name . '".']);
        }

        return redirect()->route('backend.projects.index')
            ->with('success', 'Đã xóa dự án "' . $name . '".');
    }
}

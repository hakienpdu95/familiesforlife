<?php

namespace Modules\Department\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Shared\Tenancy\Models\Organization;
use Modules\Branch\Models\Branch;
use Modules\Department\Actions\Backend\DestroyDepartmentAction;
use Modules\Department\Actions\Backend\StoreDepartmentAction;
use Modules\Department\Actions\Backend\UpdateDepartmentAction;
use Modules\Department\Data\Requests\StoreDepartmentData;
use Modules\Department\Data\Requests\UpdateDepartmentData;
use Modules\Department\Enums\DepartmentFunction;
use Modules\Department\Enums\DepartmentStatus;
use Modules\Department\Models\Department;

class DepartmentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Department::class, 'department');
    }

    public function index()
    {
        $orgId = TenantContext::getOrganizationId();

        $counts = Department::withoutTenant()
            ->where('organization_id', $orgId)
            ->selectRaw(
                'COUNT(*) as total_all,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_active,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_inactive,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_merged',
                [DepartmentStatus::Active->value, DepartmentStatus::Inactive->value, DepartmentStatus::Merged->value]
            )
            ->first();

        $totalAll      = (int) ($counts->total_all      ?? 0);
        $totalActive   = (int) ($counts->total_active   ?? 0);
        $totalInactive = (int) ($counts->total_inactive ?? 0);
        $totalMerged   = (int) ($counts->total_merged   ?? 0);

        $functions = collect(DepartmentFunction::cases())
            ->map(fn ($f) => ['value' => $f->value, 'text' => $f->label()])
            ->all();

        $statuses = collect(DepartmentStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        $branchOptions = Branch::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn ($b) => ['value' => $b->id, 'text' => $b->name . ' (' . $b->code . ')'])
            ->all();

        $parentOptions = $this->buildParentOptions($orgId);

        return view('department::index', compact(
            'totalAll', 'totalActive', 'totalInactive', 'totalMerged',
            'functions', 'statuses', 'branchOptions', 'parentOptions'
        ));
    }

    public function create()
    {
        $orgId = TenantContext::getOrganizationId();

        $functions = collect(DepartmentFunction::cases())
            ->map(fn ($f) => ['value' => $f->value, 'text' => $f->label()])
            ->all();

        $statuses = collect(DepartmentStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        $branchOptions = Branch::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn ($b) => ['value' => $b->id, 'text' => $b->name . ' (' . $b->code . ')'])
            ->all();

        $parentOptions = $this->buildParentOptions($orgId);

        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();

        return view('department::create', compact('functions', 'statuses', 'branchOptions', 'parentOptions', 'organizations', 'defaultOrgId', 'orgLocked'));
    }

    public function store(Request $request, StoreDepartmentAction $action): RedirectResponse
    {
        $data = StoreDepartmentData::validateAndCreate($request->all());
        $dept = $action->handle($data);

        return redirect()->route('backend.departments.show', $dept)
            ->with('success', 'Phòng ban "' . $dept->name . '" đã được tạo thành công.');
    }

    public function show(Department $department)
    {
        $department->load(['branch', 'parent', 'children', 'mergedInto', 'createdBy', 'updatedBy']);

        return view('department::show', compact('department'));
    }

    public function edit(Department $department)
    {
        $orgId = TenantContext::getOrganizationId();

        $functions = collect(DepartmentFunction::cases())
            ->map(fn ($f) => ['value' => $f->value, 'text' => $f->label()])
            ->all();

        $statuses = collect(DepartmentStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        $branchOptions = Branch::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn ($b) => ['value' => $b->id, 'text' => $b->name . ' (' . $b->code . ')'])
            ->all();

        // Exclude self and all descendants from parent options (prevent circular refs)
        $excludeIds = Department::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('path', 'like', $department->path . '%')
            ->pluck('id')
            ->toArray();

        $parentOptions = $this->buildParentOptions($orgId, $excludeIds);

        [$organizations, , $orgLocked] = $this->_resolveOrganizations();

        $mergedIntoOptions = Department::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('id', '!=', $department->id)
            ->where('status', '!=', 'merged')
            ->orderBy('path')
            ->get(['id', 'name', 'code', 'depth'])
            ->map(fn ($d) => [
                'value' => $d->id,
                'text'  => str_repeat('— ', $d->depth) . $d->name . ' (' . $d->code . ')',
            ])
            ->all();

        return view('department::edit', compact(
            'department', 'functions', 'statuses', 'branchOptions', 'parentOptions', 'mergedIntoOptions', 'organizations', 'orgLocked'
        ));
    }

    public function update(Request $request, Department $department, UpdateDepartmentAction $action): RedirectResponse
    {
        $data = UpdateDepartmentData::validateAndCreate($request->all());
        $action->handle($department, $data);

        return redirect()->route('backend.departments.show', $department)
            ->with('success', 'Cập nhật phòng ban thành công.');
    }

    public function options(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Department::class);

        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            $orgId = $userOrgId;
        } else {
            $orgId = $request->integer('organization_id') ?: TenantContext::getOrganizationId();
        }

        $q         = $request->input('q', '');
        $forParent = $request->boolean('for_parent');

        $rows = Department::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->when($forParent, fn ($q) => $q->where('depth', '<', 2)->orderBy('path'))
            ->when(!$forParent, fn ($q) => $q->orderBy('name')->limit(30))
            ->when($q, fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->get(['id', 'name', 'code', 'depth']);

        return response()->json($rows->map(fn ($d) => [
            'id'   => $d->id,
            'text' => $forParent
                ? str_repeat('— ', $d->depth) . $d->name . ' (' . $d->code . ')'
                : $d->name,
        ]));
    }

    public function destroy(Request $request, Department $department, DestroyDepartmentAction $action): RedirectResponse|JsonResponse
    {
        $name = $action->handle($department);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa phòng ban "' . $name . '".' ]);
        }

        return redirect()->route('backend.departments.index')
            ->with('success', 'Đã xóa phòng ban "' . $name . '".');
    }

    private function _resolveOrganizations(): array
    {
        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            return [Organization::where('id', $userOrgId)->get(['id', 'name']), $userOrgId, true];
        }
        return [Organization::orderBy('name')->get(['id', 'name']), null, false];
    }

    private function buildParentOptions(int $orgId, array $excludeIds = []): array
    {
        return Department::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('depth', '<', 2)
            ->whereNotIn('id', $excludeIds)
            ->orderBy('path')
            ->get(['id', 'name', 'code', 'depth'])
            ->map(fn ($d) => [
                'value' => $d->id,
                'text'  => str_repeat('— ', $d->depth) . $d->name . ' (' . $d->code . ')',
            ])
            ->all();
    }
}

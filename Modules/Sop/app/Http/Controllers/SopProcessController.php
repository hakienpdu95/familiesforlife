<?php

namespace Modules\Sop\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Shared\Tenancy\TenantContext;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Branch\Models\Branch;
use Modules\Department\Models\Department;
use Modules\Sop\Actions\Backend\ArchiveSopProcessAction;
use Modules\Sop\Actions\Backend\StoreSopProcessAction;
use Modules\Sop\Actions\Backend\UpdateSopProcessAction;
use Modules\Sop\Data\Requests\StoreSopProcessData;
use Modules\Sop\Data\Requests\UpdateSopProcessData;
use Modules\Sop\Enums\SopStatus;
use Modules\Sop\Enums\SopType;
use Modules\Sop\Models\SopProcess;
use Modules\Sop\Models\SopStep;
use Modules\Sop\Models\SopStepRaci;

class SopProcessController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(SopProcess::class, 'sop');
    }

    public function index()
    {
        $orgId = TenantContext::getOrganizationId();

        $statuses = collect(SopStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        $types = collect(SopType::cases())
            ->map(fn ($t) => ['value' => $t->value, 'text' => $t->label()])
            ->all();

        $departments = Department::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($d) => ['value' => $d->id, 'text' => $d->name])
            ->all();

        $branches = Branch::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($b) => ['value' => $b->id, 'text' => $b->name])
            ->all();

        return view('sop::index', compact('statuses', 'types', 'departments', 'branches'));
    }

    public function create()
    {
        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();

        $types = collect(SopType::cases())
            ->map(fn ($t) => ['value' => $t->value, 'text' => $t->label()])
            ->all();

        // Resolve only when validation fails and old() values exist
        $selectedOwner  = old('owner_id')      ? User::find(old('owner_id'), ['id', 'name'])                          : null;
        $selectedDept   = old('department_id') ? Department::withoutTenant()->find(old('department_id'), ['id', 'name']) : null;
        $selectedBranch = old('branch_id')     ? Branch::withoutTenant()->find(old('branch_id'), ['id', 'name'])       : null;

        return view('sop::create', compact(
            'organizations', 'defaultOrgId', 'orgLocked',
            'types', 'selectedOwner', 'selectedDept', 'selectedBranch'
        ));
    }

    public function store(Request $request, StoreSopProcessAction $action): RedirectResponse
    {
        $data = StoreSopProcessData::validateAndCreate($request->all());
        $sop  = $action->handle($data);

        return redirect()->route('backend.sop.show', $sop)
            ->with('success', 'Quy trình "' . $sop->code . ' — ' . $sop->title . '" đã được tạo thành công.');
    }

    public function show(SopProcess $sop)
    {
        $sop->load([
            'owner:id,name',
            'department:id,name',
            'branch:id,name',
            'activeSteps.raci',
            'connectors',
            'sopRelations.relatedSop:id,uuid,code,title,status',
            'createdBy:id,name',
            'updatedBy:id,name',
            'approvedBy:id,name',
        ]);

        return view('sop::show', compact('sop'));
    }

    public function edit(SopProcess $sop)
    {
        [$organizations, , $orgLocked] = $this->_resolveOrganizations();

        $types = collect(SopType::cases())
            ->map(fn ($t) => ['value' => $t->value, 'text' => $t->label()])
            ->all();

        $sop->load(['department', 'branch', 'owner']);

        // Override with old() values only when validation fails on re-render
        $selectedOwner  = old('owner_id')      ? User::find(old('owner_id'), ['id', 'name'])                          : null;
        $selectedDept   = old('department_id') ? Department::withoutTenant()->find(old('department_id'), ['id', 'name']) : null;
        $selectedBranch = old('branch_id')     ? Branch::withoutTenant()->find(old('branch_id'), ['id', 'name'])       : null;

        return view('sop::edit', compact(
            'sop', 'organizations', 'orgLocked',
            'types', 'selectedOwner', 'selectedDept', 'selectedBranch'
        ));
    }

    public function update(Request $request, SopProcess $sop, UpdateSopProcessAction $action): RedirectResponse
    {
        // Inject existing SOP's organization_id so UpdateSopProcessData can use it in rules()
        $data = UpdateSopProcessData::validateAndCreate(
            array_merge($request->all(), ['organization_id' => $sop->organization_id])
        );
        $action->handle($sop, $data);

        return redirect()->route('backend.sop.show', $sop)
            ->with('success', 'Cập nhật quy trình "' . $sop->code . '" thành công.');
    }

    public function destroy(Request $request, SopProcess $sop, ArchiveSopProcessAction $action): RedirectResponse|JsonResponse
    {
        $code = $action->handle($sop);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã lưu trữ quy trình "' . $code . '".']);
        }

        return redirect()->route('backend.sop.index')
            ->with('success', 'Đã lưu trữ quy trình "' . $code . '".');
    }

    public function raciMatrix(SopProcess $sop)
    {
        $this->authorize('view', $sop);

        $steps = SopStep::where('sop_id', $sop->id)
            ->where('is_active', true)
            ->orderBy('position')
            ->get(['id', 'position', 'title', 'step_type']);

        $stepIds = $steps->pluck('id');

        $raciRows = SopStepRaci::whereIn('step_id', $stepIds)
            ->get()
            ->map(fn ($r) => [
                'step_id'       => $r->step_id,
                'raci_type'     => $r->raci_type,
                'assignee_name' => $r->assigneeName(),
                'assignee_type' => $r->assignee_type,
            ]);

        $assignees = $raciRows->groupBy('assignee_name')->map(function ($rows, $name) use ($stepIds) {
            $byStep = $rows->groupBy('step_id')->map(fn ($r) => $r->pluck('raci_type')->toArray());
            return ['name' => $name, 'by_step' => $byStep];
        })->sortKeys()->values();

        return view('sop::raci', compact('sop', 'steps', 'assignees'));
    }

    /**
     * DN user (organization_id != null) → chỉ thấy org của họ, field bị locked.
     * Admin (organization_id = null)    → thấy tất cả org, chọn tự do qua TomSelect.
     *
     * @return array{0: \Illuminate\Support\Collection, 1: int|null, 2: bool}
     */
    private function _resolveOrganizations(): array
    {
        $userOrgId = auth()->user()->organization_id;

        if ($userOrgId) {
            return [
                Organization::where('id', $userOrgId)->get(['id', 'name']),
                $userOrgId,
                true,
            ];
        }

        return [
            Organization::orderBy('name')->get(['id', 'name']),
            null,
            false,
        ];
    }
}

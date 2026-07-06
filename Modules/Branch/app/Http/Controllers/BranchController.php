<?php

namespace Modules\Branch\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Branch\Actions\Backend\DestroyBranchAction;
use Modules\Branch\Actions\Backend\StoreBranchAction;
use Modules\Branch\Actions\Backend\UpdateBranchAction;
use Modules\Branch\Data\Requests\StoreBranchData;
use Modules\Branch\Data\Requests\UpdateBranchData;
use Modules\Branch\Enums\BranchStatus;
use Modules\Branch\Enums\BranchType;
use Modules\Branch\Models\Branch;

class BranchController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Branch::class, 'branch');
    }

    public function index()
    {
        $orgId = TenantContext::getOrganizationId();

        $counts = Branch::withoutTenant()
            ->where('organization_id', $orgId)
            ->selectRaw(
                'COUNT(*) as total_all,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_active,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_inactive,
                 SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as total_closed',
                [BranchStatus::Active->value, BranchStatus::Inactive->value, BranchStatus::Closed->value]
            )
            ->first();

        $totalAll      = (int) ($counts->total_all      ?? 0);
        $totalActive   = (int) ($counts->total_active   ?? 0);
        $totalInactive = (int) ($counts->total_inactive ?? 0);
        $totalClosed   = (int) ($counts->total_closed   ?? 0);

        $provinces = Province::where('is_active', true)->orderBy('name')->get(['province_code', 'name']);

        $types = collect(BranchType::cases())
            ->map(fn ($t) => ['value' => $t->value, 'text' => $t->label()])
            ->all();

        $statuses = collect(BranchStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        $parentOptions = Branch::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('depth', '<', 2)
            ->orderBy('path')
            ->get(['id', 'name', 'code', 'depth'])
            ->map(fn ($b) => [
                'value' => $b->id,
                'text'  => str_repeat('— ', $b->depth) . $b->name . ' (' . $b->code . ')',
            ])
            ->all();

        return view('branch::index', compact(
            'totalAll', 'totalActive', 'totalInactive', 'totalClosed',
            'provinces', 'types', 'statuses', 'parentOptions'
        ));
    }

    public function create()
    {
        $orgId = TenantContext::getOrganizationId();

        $provinces = Province::where('is_active', true)->orderBy('name')->get(['province_code', 'name']);

        $types = collect(BranchType::cases())
            ->map(fn ($t) => ['value' => $t->value, 'text' => $t->label()])
            ->all();

        $statuses = collect(BranchStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        $parentOptions = Branch::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('depth', '<', 2)
            ->orderBy('path')
            ->get(['id', 'name', 'code', 'depth'])
            ->map(fn ($b) => [
                'value' => $b->id,
                'text'  => str_repeat('— ', $b->depth) . $b->name . ' (' . $b->code . ')',
            ])
            ->all();

        $userOrgId = auth()->user()->organization_id;
        $orgLocked = (bool) $userOrgId;
        if ($userOrgId) {
            $organizations = Organization::where('id', $userOrgId)->get(['id', 'name']);
            $defaultOrgId  = $userOrgId;
        } else {
            $organizations = Organization::orderBy('name')->get(['id', 'name']);
            $defaultOrgId  = null;
        }

        return view('branch::create', compact('provinces', 'types', 'statuses', 'parentOptions', 'organizations', 'defaultOrgId', 'orgLocked'));
    }

    public function store(Request $request, StoreBranchAction $action): RedirectResponse
    {
        $data   = StoreBranchData::validateAndCreate($request->all());
        $branch = $action->handle($data);

        return redirect()->route('backend.branches.show', $branch)
            ->with('success', 'Chi nhánh "' . $branch->name . '" đã được tạo thành công.');
    }

    public function show(Branch $branch)
    {
        $branch->load(['province', 'ward', 'parent', 'children', 'createdBy', 'updatedBy']);

        return view('branch::show', compact('branch'));
    }

    public function edit(Branch $branch)
    {
        $orgId = TenantContext::getOrganizationId();

        $provinces = Province::where('is_active', true)->orderBy('name')->get(['province_code', 'name']);

        $types = collect(BranchType::cases())
            ->map(fn ($t) => ['value' => $t->value, 'text' => $t->label()])
            ->all();

        $statuses = collect(BranchStatus::cases())
            ->map(fn ($s) => ['value' => $s->value, 'text' => $s->label()])
            ->all();

        // Exclude self and all descendants from parent options (prevent circular refs)
        $excludeIds = Branch::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('path', 'like', $branch->path . '%')
            ->pluck('id')
            ->toArray();

        $parentOptions = Branch::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('depth', '<', 2)
            ->whereNotIn('id', $excludeIds)
            ->orderBy('path')
            ->get(['id', 'name', 'code', 'depth'])
            ->map(fn ($b) => [
                'value' => $b->id,
                'text'  => str_repeat('— ', $b->depth) . $b->name . ' (' . $b->code . ')',
            ])
            ->all();

        $branch->load(['province', 'ward']);

        $userOrgId = auth()->user()->organization_id;
        $orgLocked = (bool) $userOrgId;
        if ($userOrgId) {
            $organizations = Organization::where('id', $userOrgId)->get(['id', 'name']);
        } else {
            $organizations = Organization::orderBy('name')->get(['id', 'name']);
        }

        return view('branch::edit', compact('branch', 'provinces', 'types', 'statuses', 'parentOptions', 'organizations', 'orgLocked'));
    }

    public function update(Request $request, Branch $branch, UpdateBranchAction $action): RedirectResponse
    {
        $data = UpdateBranchData::validateAndCreate($request->all());
        $action->handle($branch, $data);

        return redirect()->route('backend.branches.show', $branch)
            ->with('success', 'Cập nhật chi nhánh thành công.');
    }

    public function options(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Branch::class);

        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            $orgId = $userOrgId;
        } else {
            $orgId = $request->integer('organization_id') ?: TenantContext::getOrganizationId();
        }

        $q         = $request->input('q', '');
        $forParent = $request->boolean('for_parent');

        $rows = Branch::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->when($forParent, fn ($q) => $q->where('depth', '<', 2)->orderBy('path'))
            ->when(!$forParent, fn ($q) => $q->orderBy('name')->limit(30))
            ->when($q, fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->get(['id', 'name', 'code', 'depth']);

        return response()->json($rows->map(fn ($b) => [
            'id'   => $b->id,
            'text' => $forParent
                ? str_repeat('— ', $b->depth) . $b->name . ' (' . $b->code . ')'
                : $b->name . ' (' . $b->code . ')',
        ]));
    }

    public function destroy(Request $request, Branch $branch, DestroyBranchAction $action): RedirectResponse|JsonResponse
    {
        $name = $action->handle($branch);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa chi nhánh "' . $name . '".' ]);
        }

        return redirect()->route('backend.branches.index')
            ->with('success', 'Đã xóa chi nhánh "' . $name . '".');
    }
}

<?php

namespace Modules\Leave\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Department\Models\Department;
use Modules\JobTitle\Models\JobTitle;
use Modules\Leave\Actions\Backend\StoreLeavePolicyAction;
use Modules\Leave\Actions\Backend\UpdateLeavePolicyAction;
use Modules\Leave\Data\Requests\StoreLeavePolicyData;
use Modules\Leave\Data\Requests\UpdateLeavePolicyData;
use Modules\Leave\Enums\LeaveType;
use Modules\Leave\Models\LeavePolicy;

class LeavePolicyController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', LeavePolicy::class);

        $policies = LeavePolicy::with(['jobTitle', 'department'])
            ->orderBy('leave_type')
            ->orderBy('name')
            ->get();

        $leaveTypes = collect(LeaveType::cases())
            ->map(fn ($t) => ['value' => $t->value, 'text' => $t->label()])
            ->all();

        return view('leave::policies.index', compact('policies', 'leaveTypes'));
    }

    public function create()
    {
        $this->authorize('create', LeavePolicy::class);

        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();

        $filterOrgId = auth()->user()->organization_id;

        $jobTitleQuery = JobTitle::withoutTenant()->where('is_active', true)->orderBy('name');
        $deptQuery     = Department::withoutTenant()->where('status', 'active')->orderBy('name');

        if ($filterOrgId) {
            $jobTitleQuery->where('organization_id', $filterOrgId);
            $deptQuery->where('organization_id', $filterOrgId);
        }

        $jobTitles   = $jobTitleQuery->get(['id', 'name', 'category']);
        $departments = $deptQuery->get(['id', 'name']);

        $leaveTypes = collect(LeaveType::cases())
            ->map(fn ($t) => ['value' => $t->value, 'text' => $t->label()])
            ->all();

        return view('leave::policies.create', compact(
            'organizations', 'defaultOrgId', 'orgLocked',
            'jobTitles', 'departments', 'leaveTypes'
        ));
    }

    public function store(Request $request, StoreLeavePolicyAction $action): RedirectResponse
    {
        $this->authorize('create', LeavePolicy::class);

        $data = StoreLeavePolicyData::validateAndCreate($request->all());
        $action->handle($data);

        return redirect()->route('backend.leave.policies.index')
            ->with('success', 'Chính sách nghỉ phép đã được tạo thành công.');
    }

    public function edit(LeavePolicy $policy)
    {
        $this->authorize('update', $policy);

        $orgId = $policy->organization_id;

        $jobTitles = JobTitle::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'category']);

        $departments = Department::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $leaveTypes = collect(LeaveType::cases())
            ->map(fn ($t) => ['value' => $t->value, 'text' => $t->label()])
            ->all();

        $orgName = Organization::find($orgId)?->name ?? '';

        return view('leave::policies.edit', compact('policy', 'jobTitles', 'departments', 'leaveTypes', 'orgName'));
    }

    public function update(Request $request, LeavePolicy $policy, UpdateLeavePolicyAction $action): RedirectResponse
    {
        $this->authorize('update', $policy);

        $data = UpdateLeavePolicyData::validateAndCreate($request->all());
        $action->handle($policy, $data);

        return redirect()->route('backend.leave.policies.index')
            ->with('success', 'Chính sách nghỉ phép đã được cập nhật.');
    }

    public function destroy(LeavePolicy $policy): RedirectResponse
    {
        $this->authorize('delete', $policy);

        $policy->delete();

        return redirect()->route('backend.leave.policies.index')
            ->with('success', 'Đã xóa chính sách nghỉ phép.');
    }

    private function _resolveOrganizations(): array
    {
        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            return [Organization::where('id', $userOrgId)->get(['id', 'name']), $userOrgId, true];
        }
        return [Organization::orderBy('name')->get(['id', 'name']), null, false];
    }
}

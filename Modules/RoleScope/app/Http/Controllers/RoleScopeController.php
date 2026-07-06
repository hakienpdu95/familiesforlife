<?php

namespace Modules\RoleScope\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Branch\Models\Branch;
use Modules\Department\Models\Department;
use Modules\RoleScope\Actions\Backend\GrantRoleScopeAction;
use Modules\RoleScope\Actions\Backend\RevokeRoleScopeAction;
use Modules\RoleScope\Actions\Backend\UpdateRoleScopeAction;
use Modules\RoleScope\Data\Requests\GrantRoleScopeData;
use Modules\RoleScope\Data\Requests\UpdateRoleScopeData;
use Modules\RoleScope\Models\UserRoleScope;
use Spatie\Permission\Models\Role;

class RoleScopeController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(UserRoleScope::class, 'role_scope');
    }

    public function index()
    {
        $userOrgId = auth()->user()->organization_id;

        $counts = UserRoleScope::withoutTenant()
            ->when($userOrgId, fn ($q, $id) => $q->where('organization_id', $id))
            ->selectRaw(
                'COUNT(*) as total_all,
                 SUM(CASE WHEN expires_at IS NULL OR expires_at > NOW() THEN 1 ELSE 0 END) as total_active,
                 SUM(CASE WHEN expires_at IS NOT NULL AND expires_at <= NOW() THEN 1 ELSE 0 END) as total_expired,
                 SUM(CASE WHEN scope_branch_id IS NULL AND scope_dept_id IS NULL THEN 1 ELSE 0 END) as total_org_scope'
            )
            ->first();

        $totalAll      = (int) ($counts->total_all       ?? 0);
        $totalActive   = (int) ($counts->total_active    ?? 0);
        $totalExpired  = (int) ($counts->total_expired   ?? 0);
        $totalOrgScope = (int) ($counts->total_org_scope ?? 0);

        $roles = Role::orderBy('name')->get(['id', 'name']);

        $scopeLevels = [
            ['value' => 'org',    'text' => 'Toàn tổ chức'],
            ['value' => 'branch', 'text' => 'Chi nhánh'],
            ['value' => 'dept',   'text' => 'Phòng ban'],
        ];

        $statuses = [
            ['value' => 'active',  'text' => 'Còn hiệu lực'],
            ['value' => 'expired', 'text' => 'Đã hết hạn'],
        ];

        return view('rolescope::index', compact(
            'totalAll', 'totalActive', 'totalExpired', 'totalOrgScope',
            'roles', 'scopeLevels', 'statuses'
        ));
    }

    public function create()
    {
        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();

        // For locked org users, pre-load related data server-side.
        // For admin users, data is loaded dynamically via JS/API when org is selected.
        $users = $branches = $departments = collect();
        if ($orgLocked) {
            $lockedOrgId = auth()->user()->organization_id;
            $users = User::where('organization_id', $lockedOrgId)
                ->orderBy('name')->get(['id', 'name', 'email']);
            $branches = Branch::withoutTenant()
                ->where('organization_id', $lockedOrgId)->where('status', 'active')
                ->orderBy('name')->get(['id', 'name', 'code']);
            $departments = Department::withoutTenant()
                ->where('organization_id', $lockedOrgId)->where('status', 'active')
                ->orderBy('name')->get(['id', 'name', 'code', 'branch_id']);
        }

        $roles = Role::orderBy('name')->get(['id', 'name']);

        return view('rolescope::create', compact(
            'organizations', 'defaultOrgId', 'orgLocked',
            'users', 'roles', 'branches', 'departments'
        ));
    }

    public function store(Request $request, GrantRoleScopeAction $action): RedirectResponse
    {
        $request->validate($this->_grantValidationRules($request));

        $data  = GrantRoleScopeData::validateAndCreate($request->all());
        $scope = $action->handle($data);

        return redirect()->route('backend.role-scopes.show', $scope)
            ->with('success', 'Đã cấp quyền thành công.');
    }

    public function show(UserRoleScope $roleScope)
    {
        $roleScope->loadMissing([
            'user:id,name,email',
            'role:id,name',
            'scopeBranch:id,name,code',
            'scopeDept:id,name,code',
            'grantedByUser:id,name',
        ]);

        return view('rolescope::show', compact('roleScope'));
    }

    public function edit(UserRoleScope $roleScope)
    {
        $roleScope->loadMissing([
            'user:id,name,email',
            'role:id,name',
            'scopeBranch:id,name',
            'scopeDept:id,name',
        ]);

        return view('rolescope::edit', compact('roleScope'));
    }

    public function update(Request $request, UserRoleScope $roleScope, UpdateRoleScopeAction $action): RedirectResponse
    {
        $request->validate([
            'expires_at' => ['nullable', 'date', 'after:now'],
            'note'       => ['nullable', 'string', 'max:500'],
        ]);

        $data = UpdateRoleScopeData::validateAndCreate($request->all());
        $action->handle($roleScope, $data);

        return redirect()->route('backend.role-scopes.show', $roleScope)
            ->with('success', 'Đã cập nhật phân quyền thành công.');
    }

    public function destroy(Request $request, UserRoleScope $roleScope, RevokeRoleScopeAction $action): RedirectResponse|JsonResponse
    {
        $result = $action->handle($roleScope);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã thu hồi quyền của "' . $result['userName'] . '".' ]);
        }

        return redirect()->route('backend.role-scopes.index')
            ->with('success', 'Đã thu hồi quyền "' . $result['roleName'] . '" của "' . $result['userName'] . '".');
    }

    public function apiOrgData(Request $request): JsonResponse
    {
        $orgId = (int) $request->input('org_id');
        if (!$orgId) {
            return response()->json(['users' => [], 'branches' => [], 'departments' => []]);
        }

        $users = User::where('organization_id', $orgId)
            ->orderBy('name')->get(['id', 'name', 'email'])
            ->map(fn ($u) => ['value' => $u->id, 'text' => $u->name . ' (' . $u->email . ')'])
            ->values();

        $branches = Branch::withoutTenant()
            ->where('organization_id', $orgId)->where('status', 'active')
            ->orderBy('name')->get(['id', 'name', 'code'])
            ->map(fn ($b) => ['value' => $b->id, 'text' => $b->name . ' [' . $b->code . ']'])
            ->values();

        $departments = Department::withoutTenant()
            ->where('organization_id', $orgId)->where('status', 'active')
            ->orderBy('name')->get(['id', 'name', 'code', 'branch_id'])
            ->map(fn ($d) => ['value' => $d->id, 'text' => $d->name . ' [' . $d->code . ']', 'branch_id' => $d->branch_id])
            ->values();

        return response()->json(compact('users', 'branches', 'departments'));
    }

    private function _grantValidationRules(Request $request): array
    {
        $orgId = (int) $request->input('organization_id');

        $rules = [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'user_id'         => ['required', 'integer', 'exists:users,id'],
            'role_id'         => ['required', 'integer', 'exists:roles,id'],
            'scope_branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'scope_dept_id'   => ['nullable', 'integer', 'exists:departments,id'],
            'expires_at'      => ['nullable', 'date', 'after:now'],
            'note'            => ['nullable', 'string', 'max:500'],
        ];

        // scope_dept requires scope_branch
        if ($request->filled('scope_dept_id') && ! $request->filled('scope_branch_id')) {
            $rules['scope_branch_id'][] = 'required';
        }

        return $rules;
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

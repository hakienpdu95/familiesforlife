<?php

namespace Modules\User\Http\Controllers;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Organization\Models\Organization;
use Modules\User\Actions\DestroyUserAction;
use Modules\User\Actions\StoreUserAction;
use Modules\User\Actions\UpdateUserAction;
use Modules\User\Data\StoreUserData;
use Modules\User\Data\UpdateUserData;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $isAdmin   = $request->user()->hasAnyRole(['super-admin', RoleEnum::ADMIN->value]);
        $canEdit   = $request->user()->can('create', User::class);
        $canDelete = $isAdmin;

        $countQuery = User::whereNotNull('organization_id');
        if (! $isAdmin) {
            $countQuery->where('organization_id', $request->user()->organization_id);
        }

        $counts = $countQuery->selectRaw(
            'COUNT(*) as total_all,
             SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as total_active,
             SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as total_inactive'
        )->first();

        $roles = collect(RoleEnum::cases())
            ->map(fn ($r) => ['value' => $r->value, 'text' => $r->label()])
            ->all();

        $statuses = [
            ['value' => '1', 'text' => 'Hoạt động'],
            ['value' => '0', 'text' => 'Vô hiệu'],
        ];

        $organizations = $isAdmin
            ? Organization::orderBy('name')->get(['id', 'name'])
            : collect();

        return view('user::index', compact('isAdmin', 'canEdit', 'canDelete', 'roles', 'statuses', 'organizations', 'counts'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', User::class);

        $isAdmin = $request->user()->hasAnyRole(['super-admin', RoleEnum::ADMIN->value]);

        $organizations = $this->getOrganizationsFor($request->user());
        $roles         = $this->buildRolesFor($request->user());
        $matrix        = $this->permissionMatrix();

        return view('user::create', compact('organizations', 'roles', 'matrix', 'isAdmin'));
    }

    public function store(Request $request, StoreUserAction $action): RedirectResponse
    {
        $this->authorize('create', User::class);

        // Guard: HR cannot assign roles beyond their allowed set
        $this->guardRoleEscalation($request->user(), $request->input('system_role'));

        $orgId        = $request->user()->organization_id;
        $currentCount = User::where('organization_id', $orgId)->count();
        if (org_at_limit('limit.members', $currentCount)) {
            return back()->withInput()->withErrors([
                'limit' => 'Bạn đã đạt giới hạn người dùng (' . org_limit('limit.members') . ') của gói hiện tại. Vui lòng nâng cấp để thêm.',
            ]);
        }

        try {
            $data = StoreUserData::validateAndCreate($request->all());
            $user = $action->handle($data);
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['email' => $e->getMessage()]);
        }

        return redirect()->route('backend.users.index')
            ->with('success', 'Tài khoản "' . $user->name . '" đã được tạo thành công.');
    }

    public function edit(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $isAdmin     = $request->user()->hasAnyRole(['super-admin', RoleEnum::ADMIN->value]);
        $currentRole = $this->resolveUserRole($user);

        $user->load(['organization', 'organizationMembership']);

        $organizations = $this->getOrganizationsFor($request->user());
        $roles         = $this->buildRolesFor($request->user());
        $matrix        = $this->permissionMatrix();

        return view('user::edit', compact('user', 'organizations', 'roles', 'matrix', 'isAdmin', 'currentRole'));
    }

    public function update(Request $request, User $user, UpdateUserAction $action): RedirectResponse
    {
        $this->authorize('update', $user);

        $this->guardRoleEscalation($request->user(), $request->input('system_role'));

        $data = UpdateUserData::validateAndCreate($request->all());
        $action->handle($user, $data);

        return redirect()->route('backend.users.index')
            ->with('success', 'Cập nhật tài khoản thành công.');
    }

    public function destroy(Request $request, User $user, DestroyUserAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $user);

        $name = $action->handle($user);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa tài khoản "' . $name . '".' ]);
        }

        return redirect()->route('backend.users.index')
            ->with('success', 'Đã xóa tài khoản "' . $name . '".');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function getOrganizationsFor(User $actor)
    {
        if ($actor->hasAnyRole(['super-admin', RoleEnum::ADMIN->value])) {
            return Organization::where('status', 'active')->orderBy('name')->get(['id', 'name']);
        }

        return Organization::where('id', $actor->organization_id)->get(['id', 'name']);
    }

    private function buildRolesFor(User $actor): array
    {
        $isAdmin = $actor->hasAnyRole(['super-admin', RoleEnum::ADMIN->value]);

        // HR cannot create CEO or System Admin accounts
        $excluded = $isAdmin ? [] : [RoleEnum::CEO->value, RoleEnum::ADMIN->value];

        return collect(RoleEnum::cases())
            ->reject(fn ($r) => in_array($r->value, $excluded, true))
            ->map(fn ($r) => ['value' => $r->value, 'label' => $r->label()])
            ->values()
            ->all();
    }

    private function guardRoleEscalation(User $actor, ?string $requestedRole): void
    {
        if ($requestedRole === null) return;

        $restricted = [RoleEnum::CEO->value, RoleEnum::ADMIN->value];
        $isAdmin    = $actor->hasAnyRole(['super-admin', RoleEnum::ADMIN->value]);

        if (! $isAdmin && in_array($requestedRole, $restricted, true)) {
            abort(403, 'Bạn không có quyền gán vai trò này.');
        }
    }

    /**
     * Get the user's current system role scoped to their own organisation,
     * regardless of the currently logged-in user's team context.
     */
    private function resolveUserRole(User $user): string
    {
        $prevTeamId = getPermissionsTeamId();
        setPermissionsTeamId($user->organization_id);
        $user->unsetRelation('roles');
        $role = $user->getRoleNames()->first() ?? '';
        setPermissionsTeamId($prevTeamId);
        $user->unsetRelation('roles');

        return $role;
    }

    private function permissionMatrix(): array
    {
        return [
            'CEO Dashboard' => ['ceo' => 'Full',         'ops' => 'Limited',       'ai_operator' => 'Limited',       'system_admin' => 'Config',       'viewer' => 'View ltd'],
            'CRM Leads'     => ['ceo' => 'Full',         'sales' => 'Assigned',    'ops' => 'Limited',               'marketing' => 'Source view',     'ai_operator' => 'Limited', 'system_admin' => 'Config'],
            'Sales AI'      => ['ceo' => 'Full',         'sales' => 'Use',         'marketing' => 'Limited',         'ai_operator' => 'Config prompt',  'system_admin' => 'Config'],
            'Tasks'         => ['ceo' => 'Full',         'sales' => 'Assigned',    'ops' => 'Full team',             'marketing' => 'Limited',          'hr' => 'HR tasks',         'ai_operator' => 'Limited',  'system_admin' => 'Config', 'viewer' => 'View ltd'],
            'SOP'           => ['ceo' => 'Approve/View', 'sales' => 'View related','ops' => 'Create/Edit',           'marketing' => 'View related',     'hr' => 'Create HR SOP',    'ai_operator' => 'AI config','system_admin' => 'Config', 'viewer' => 'View ltd'],
            'Workflow'      => ['ceo' => 'Monitor',      'sales' => 'Limited',     'ops' => 'Monitor/Edit',          'marketing' => 'Limited',          'hr' => 'Limited',          'ai_operator' => 'AI config','system_admin' => 'Full config'],
            'Prompt Mgmt'   => ['ceo' => 'View',         'ai_operator' => 'Full',  'system_admin' => 'Admin config'],
            'AI Logs'       => ['ceo' => 'View summary', 'ops' => 'Limited',       'ai_operator' => 'Full',          'system_admin' => 'Full'],
            'Users'         => ['ceo' => 'View',         'hr' => 'Limited',        'system_admin' => 'Full'],
            'Roles/Perms'   => ['system_admin' => 'Full'],
            'Reports'       => ['ceo' => 'Full',         'sales' => 'Personal/team','ops' => 'Operations',           'marketing' => 'Marketing',        'hr' => 'HR',               'ai_operator' => 'AI usage', 'system_admin' => 'Full',   'viewer' => 'Shared only'],
        ];
    }
}

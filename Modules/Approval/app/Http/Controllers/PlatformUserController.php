<?php

namespace Modules\Approval\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Approval\Data\StorePlatformUserData;
use Modules\Approval\Data\UpdatePlatformUserData;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * spec/Platform_RBAC_Phase2_Specification.md §2 — CRUD nhân sự Platform
 * (organization_id=null), thay cho giải pháp tạm `platform:user-create` CLI
 * (spec/Platform_RBAC_Technical_Specification.md §3.8). Chỉ `super-admin` truy cập được.
 *
 * CỐ Ý không tạo được role `super-admin` qua đây (§2.4/§3.8) — giữ nguyên quyết định coi
 * đó là role nhạy cảm nhất, luôn phải qua AuthDatabaseSeeder thủ công có review.
 */
class PlatformUserController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless($request->user()?->hasRole('super-admin'), 403);

            return $next($request);
        });
    }

    public function index(): View
    {
        $users = User::withoutGlobalScopes()
            ->whereNull('organization_id')
            ->with('roles:id,name')
            ->orderBy('name')
            ->paginate(20);

        return view('approval::platform-users.index', [
            'users'  => $users,
            'labels' => User::platformRoleLabels(),
        ]);
    }

    public function create(): View
    {
        return view('approval::platform-users.create', [
            'labels' => collect(User::platformRoleLabels())->except('super-admin'),
        ]);
    }

    public function store(): RedirectResponse
    {
        $data = StorePlatformUserData::validateAndCreate(request()->all());

        if (! Role::where('name', $data->role)->where('guard_name', 'web')->exists()) {
            return back()->withInput()->withErrors([
                'role' => "Role \"{$data->role}\" chưa tồn tại trong bảng roles — chạy seeder tương ứng trước.",
            ]);
        }

        $user = new User();
        $user->forceFill([
            'name'              => $data->name,
            'email'             => $data->email,
            'password'          => Hash::make($data->password),
            'organization_id'   => null,
            'email_verified_at' => now(),
        ])->save();

        setPermissionsTeamId(null);
        $user->assignRole($data->role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        ActivityLogger::info('User', 'platform_user_created', $user, [
            'email'       => $data->email,
            'role'        => $data->role,
            'created_via' => 'admin_ui',
        ]);

        return redirect()->route('backend.platform-users.index')
            ->with('success', "Đã tạo user Platform: {$data->email}.");
    }

    public function edit(User $platformUser): View
    {
        abort_if($platformUser->organization_id !== null, 404);

        return view('approval::platform-users.edit', [
            'platformUser' => $platformUser,
            'labels'       => collect(User::platformRoleLabels())->except('super-admin'),
        ]);
    }

    public function update(User $platformUser): RedirectResponse
    {
        abort_if($platformUser->organization_id !== null, 404);
        abort_if($platformUser->hasRole('super-admin'), 403);

        $data = UpdatePlatformUserData::validateAndCreate(request()->all());

        if (! Role::where('name', $data->role)->where('guard_name', 'web')->exists()) {
            return back()->withInput()->withErrors([
                'role' => "Role \"{$data->role}\" chưa tồn tại trong bảng roles — chạy seeder tương ứng trước.",
            ]);
        }

        $platformUser->update(['name' => $data->name]);

        setPermissionsTeamId(null);
        $platformUser->syncRoles([$data->role]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        ActivityLogger::info('User', 'platform_user_role_changed', $platformUser, ['role' => $data->role]);

        return redirect()->route('backend.platform-users.index')->with('success', 'Đã cập nhật.');
    }

    public function destroy(User $platformUser): RedirectResponse
    {
        abort_if($platformUser->organization_id !== null, 404);
        abort_if($platformUser->hasRole('super-admin'), 403);
        abort_if($platformUser->id === auth()->id(), 403);

        // Vô hiệu hoá, KHÔNG xoá cứng — giữ audit trail (is_active đã có sẵn, kiểm tra thật bởi
        // EnsureUserIsActive middleware).
        $platformUser->update(['is_active' => false]);

        ActivityLogger::info('User', 'platform_user_deactivated', $platformUser);

        return redirect()->route('backend.platform-users.index')->with('success', 'Đã vô hiệu hoá tài khoản.');
    }
}

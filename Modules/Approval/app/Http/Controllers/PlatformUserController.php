<?php

namespace Modules\Approval\Http\Controllers;

use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
            setPermissionsTeamId(null);
            Gate::authorize('platform-users.manage');

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
            'labels' => collect(User::platformRoleLabels())->only(User::assignablePlatformRoles()),
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

        $user = DB::transaction(function () use ($data) {
            $user = new User();
            $user->forceFill([
                'name'              => $data->name,
                'email'             => $data->email,
                'password'          => Hash::make($data->password),
                'organization_id'   => null,
                'email_verified_at' => now(),
                'account_type'      => AccountType::Platform,
            ])->save();

            $user->assignRole($data->role);

            return $user;
        });

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
        Gate::authorize('platform-users.update', $platformUser);

        return view('approval::platform-users.edit', [
            'platformUser' => $platformUser,
            'labels'       => collect(User::platformRoleLabels())->only(User::assignablePlatformRoles()),
        ]);
    }

    public function update(User $platformUser): RedirectResponse
    {
        Gate::authorize('platform-users.update', $platformUser);

        $data = UpdatePlatformUserData::validateAndCreate(request()->all());

        if (! Role::where('name', $data->role)->where('guard_name', 'web')->exists()) {
            return back()->withInput()->withErrors([
                'role' => "Role \"{$data->role}\" chưa tồn tại trong bảng roles — chạy seeder tương ứng trước.",
            ]);
        }

        DB::transaction(function () use ($platformUser, $data) {
            $platformUser->update(['name' => $data->name]);
            $platformUser->syncRoles([$data->role]);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        ActivityLogger::info('User', 'platform_user_role_changed', $platformUser, ['role' => $data->role]);

        return redirect()->route('backend.platform-users.index')->with('success', 'Đã cập nhật.');
    }

    public function destroy(User $platformUser): RedirectResponse
    {
        Gate::authorize('platform-users.deactivate', $platformUser);

        // Vô hiệu hoá, KHÔNG xoá cứng — giữ audit trail (is_active đã có sẵn, kiểm tra thật bởi
        // EnsureUserIsActive middleware).
        $platformUser->update(['is_active' => false]);

        $this->terminateSessions($platformUser);

        ActivityLogger::info('User', 'platform_user_deactivated', $platformUser);

        return redirect()->route('backend.platform-users.index')->with('success', 'Đã vô hiệu hoá tài khoản.');
    }

    public function resetPassword(User $platformUser): RedirectResponse
    {
        Gate::authorize('platform-users.resetPassword', $platformUser);

        $defaultPassword = config('approval.platform_users.default_password');

        $platformUser->forceFill([
            'password'       => Hash::make($defaultPassword),
            'remember_token' => null,
        ])->save();

        $this->terminateSessions($platformUser);

        ActivityLogger::info('User', 'platform_user_password_reset', $platformUser);

        return redirect()->route('backend.platform-users.index')
            ->with('success', "Đã reset mật khẩu của {$platformUser->email}. Mật khẩu mới: {$defaultPassword}");
    }

    public function activate(User $platformUser): RedirectResponse
    {
        Gate::authorize('platform-users.activate', $platformUser);

        $platformUser->update(['is_active' => true]);

        ActivityLogger::info('User', 'platform_user_activated', $platformUser);

        return redirect()->route('backend.platform-users.index')->with('success', 'Đã kích hoạt lại tài khoản.');
    }

    private function terminateSessions(User $user): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
    }
}

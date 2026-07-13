<?php

namespace Modules\Approval\Database\Seeders;

use App\Enums\AccountType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seed 2 role Platform mới (Lớp A) — spec/Platform_RBAC_Technical_Specification.md §3.3:
 *
 *  - platform_ops: quản lý subscription tổ chức (được gán permission subscription.* có sẵn
 *    của Modules/Subscription — route dashboard/subscription/admin/* đã gate bằng
 *    can:subscription.admin, không cần sửa route/controller). KHÔNG có ability nào trên
 *    approve/reject/publishApproval/archiveApproval.
 *  - platform_viewer: chỉ xem (Gate viewDashboard/viewApprovalHistory đã OR thêm
 *    isPlatformViewer() ở ApprovalServiceProvider::boot()) — không được cấp permission ghi nào.
 *
 * Cùng quy ước tài khoản organization_id=null như ContentModeratorSeeder.
 *
 * Tài khoản mặc định: ops@system.local, viewer@system.local (mật khẩu Admin@123!)
 * ⚠️  Đổi mật khẩu ngay sau khi deploy production.
 */
class PlatformOpsViewerSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        setPermissionsTeamId(null);
        $opsRole    = Role::firstOrCreate(['name' => 'platform_ops', 'guard_name' => 'web']);
        $viewerRole = Role::firstOrCreate(['name' => 'platform_viewer', 'guard_name' => 'web']);

        $subscriptionPermissions = Permission::where('guard_name', 'web')
            ->where(function ($query) {
                $query->where('name', 'like', 'subscription.%');
            })
            ->get();

        if ($subscriptionPermissions->isNotEmpty()) {
            $opsRole->syncPermissions($subscriptionPermissions);
        }

        // platform_viewer CỐ Ý không được syncPermissions bất kỳ permission ghi nào — quyền
        // xem dashboard/lịch sử duyệt đến từ Gate OR isPlatformViewer(), không qua permission.
        $this->createAccount('ops@system.local', 'Platform Ops', $opsRole);
        $this->createAccount('viewer@system.local', 'Platform Viewer', $viewerRole);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ platform_ops + platform_viewer roles seeded (ops@system.local, viewer@system.local).');
    }

    private function createAccount(string $email, string $name, Role $role): void
    {
        $user = User::withoutGlobalScopes()->firstOrCreate(
            ['email' => $email],
            [
                'name'              => $name,
                'password'          => Hash::make('Admin@123!'),
                'organization_id'   => null,
                'email_verified_at' => now(),
                'trust_level'       => 2,
                'account_type'      => AccountType::Platform,
            ]
        );

        if (! $user->wasRecentlyCreated) {
            $user->forceFill(['account_type' => AccountType::Platform])->save();
        }

        setPermissionsTeamId(null);
        $user->syncRoles($role);
    }
}

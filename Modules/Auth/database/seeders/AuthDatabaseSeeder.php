<?php

namespace Modules\Auth\Database\Seeders;

use App\Enums\AccountType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seed: tạo role super-admin + 2 tài khoản quản trị hệ thống mặc định.
 *
 * super-admin:
 *  - Bypass toàn bộ Gate checks (xem AppServiceProvider::Gate::before)
 *  - Có tất cả permissions hiện tại
 *
 * Tài khoản mặc định:
 *  admin@vigiadinh.vn        / Admin@123!
 *  super-admin@vigiadinh.vn  / Admin@123!
 *
 * ⚠️  Đổi mật khẩu ngay sau khi deploy production.
 */
class AuthDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $superAdminRole = $this->createSuperAdminRole();
        $this->createSystemAdmins($superAdminRole);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ super-admin role + 2 system accounts seeded.');
    }

    // ── Tạo role super-admin với toàn bộ permissions ──────────────────
    private function createSuperAdminRole(): Role
    {
        $role = Role::firstOrCreate([
            'name'       => 'super-admin',
            'guard_name' => 'web',
        ]);

        // Sync toàn bộ permissions hiện có vào super-admin
        $role->syncPermissions(Permission::all());

        return $role;
    }

    // ── Tạo 2 tài khoản quản trị hệ thống mặc định ───────────────────
    private function createSystemAdmins(Role $role): void
    {
        $now = now();

        $admins = [
            [
                'name'  => 'System Administrator',
                'email' => 'admin@vigiadinh.vn',
            ],
            [
                'name'  => 'Super Administrator',
                'email' => 'super-admin@vigiadinh.vn',
            ],
        ];

        foreach ($admins as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'             => $data['name'],
                    'password'         => Hash::make('Admin@123!'),
                    // Email pre-verified, trust_level = 2 (bypass toàn bộ eKYC)
                    'email_verified_at' => $now,
                    'trust_level'       => 2,
                    'account_type'      => AccountType::Platform,
                ]
            );

            // Nếu user đã tồn tại từ seed trước, đảm bảo luôn ở trạng thái fully verified
            if (! $user->wasRecentlyCreated) {
                $user->forceFill([
                    'email_verified_at' => $user->email_verified_at ?? $now,
                    'trust_level'       => max($user->trust_level, 2),
                    'account_type'      => AccountType::Platform,
                ])->save();
            }

            $user->syncRoles($role);
        }
    }
}

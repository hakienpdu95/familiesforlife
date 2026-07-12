<?php

namespace Modules\Approval\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seed 2 permission thuộc về chính module Approval (spec/Workflow_Approval_Technical_Specification.md
 * §11): `approval.view_dashboard` (gate dashboard "chờ duyệt của tôi" — chỉ pending item user
 * tự duyệt được) và `approval.view_history` (gate trang lịch sử duyệt ĐẦY ĐỦ — mọi entity, mọi
 * trạng thái, dành cho vai trò giám sát). Mọi permission khác liên quan tới duyệt (vd
 * product.publish) thuộc về module tiêu thụ, seed riêng ở module đó (xem
 * Modules/Product/database/seeders/ProductPermissionSeeder.php).
 *
 * Chạy: php artisan db:seed --class="Modules\Approval\Database\Seeders\ApprovalPermissionSeeder"
 */
class ApprovalPermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'approval.view_dashboard',
        'approval.view_history',
    ];

    private const ROLE_MAP = [
        'ceo' => [
            'approval.view_dashboard',
            'approval.view_history',
        ],
        'ops' => [
            'approval.view_dashboard',
        ],
        'system_admin' => [
            'approval.view_dashboard',
            'approval.view_history',
        ],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate([
                'name'       => $name,
                'guard_name' => 'web',
            ]);
        }

        foreach (self::ROLE_MAP as $roleName => $perms) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo($perms);
            }
        }

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ Approval permissions seeded.');
    }
}

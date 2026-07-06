<?php

namespace Modules\BusinessSolution\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seed permission solution_catalog.view / solution_catalog.manage và gán vào role.
 * Chạy: php artisan db:seed --class="Modules\BusinessSolution\Database\Seeders\BusinessSolutionPermissionSeeder"
 */
class BusinessSolutionPermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'solution_catalog.view',
        'solution_catalog.manage',
    ];

    private const ROLE_MAP = [
        // System Admin: quản lý toàn bộ danh mục Business Solution
        'system_admin' => ['solution_catalog.view', 'solution_catalog.manage'],
        // Tất cả role còn lại: chỉ xem — cần biết tổ chức có thể kích hoạt Solution nào
        'ceo'          => ['solution_catalog.view'],
        'sales'        => ['solution_catalog.view'],
        'ops'          => ['solution_catalog.view'],
        'marketing'    => ['solution_catalog.view'],
        'hr'           => ['solution_catalog.view'],
        'ai_operator'  => ['solution_catalog.view'],
        'viewer'       => ['solution_catalog.view'],
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

        $this->command?->info('  ✓ BusinessSolution permissions seeded.');
    }
}

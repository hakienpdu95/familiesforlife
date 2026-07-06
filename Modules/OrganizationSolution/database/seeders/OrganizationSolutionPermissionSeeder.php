<?php

namespace Modules\OrganizationSolution\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seed permission organization_solution.* và gán cho CEO (chủ tổ chức, tự kích hoạt/
 * cấu hình/tạm ngưng Solution của mình) + System Admin (hỗ trợ).
 */
class OrganizationSolutionPermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'organization_solution.activate',
        'organization_solution.configure',
        'organization_solution.suspend',
        'organization_solution.archive',
    ];

    private const ROLES = ['ceo', 'system_admin'];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (self::ROLES as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo(self::PERMISSIONS);
            }
        }

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command?->info('  ✓ OrganizationSolution permissions seeded.');
    }
}

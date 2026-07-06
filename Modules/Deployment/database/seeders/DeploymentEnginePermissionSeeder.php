<?php

namespace Modules\Deployment\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seed permission deployment_engine.* (chạy DeployOrganizationSolutionAction + xem
 * log/snapshot) và gán cho CEO (chủ tổ chức) + System Admin (hỗ trợ).
 */
class DeploymentEnginePermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'deployment_engine.run',
        'deployment_engine.view_logs',
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

        $this->command?->info('  ✓ Deployment Engine permissions seeded.');
    }
}

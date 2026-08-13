<?php

namespace Modules\EntityComparison\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** spec/Entity_Comparison_Module_Technical_Spec.md §10 — đúng mẫu OcopPermissionSeeder, 1 permission thô. */
class EntityComparisonPermissionSeeder extends Seeder
{
    private const PERMISSIONS = ['entity_comparison.manage'];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (['platform_ops', 'platform_content_head', 'platform_content_editor'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            $role?->givePermissionTo('entity_comparison.manage');
        }

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        $superAdmin?->syncPermissions(Permission::all());

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}

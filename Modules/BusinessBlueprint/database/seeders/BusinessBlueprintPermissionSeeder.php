<?php

namespace Modules\BusinessBlueprint\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seed permission blueprint.* và gán vào role. Ma trận gốc (A04.2 §6) cần role
 * "Business Analyst"/"Product Owner" (chưa tồn tại) — tạm gán toàn bộ quyền tác giả
 * cho system_admin (xem PermissionEnum::BLUEPRINT_*); VIEW mở cho tất cả role.
 */
class BusinessBlueprintPermissionSeeder extends Seeder
{
    private const AUTHOR_PERMISSIONS = [
        'blueprint.create', 'blueprint.edit', 'blueprint.delete',
        'blueprint.publish', 'blueprint.archive', 'blueprint.clone',
    ];

    private const VIEW_PERMISSION = 'blueprint.view';

    private const VIEW_ROLES = [
        'system_admin', 'ceo', 'sales', 'ops', 'marketing', 'hr', 'ai_operator', 'viewer',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ([...self::AUTHOR_PERMISSIONS, self::VIEW_PERMISSION] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $systemAdmin = Role::where('name', 'system_admin')->where('guard_name', 'web')->first();
        if ($systemAdmin) {
            $systemAdmin->givePermissionTo(self::AUTHOR_PERMISSIONS);
        }

        foreach (self::VIEW_ROLES as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo(self::VIEW_PERMISSION);
            }
        }

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command?->info('  ✓ BusinessBlueprint permissions seeded.');
    }
}

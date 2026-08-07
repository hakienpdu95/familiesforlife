<?php

namespace Modules\PromptFrameworkStudio\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * spec/PromptFrameworkStudio_Technical_Specification.md §6 — cùng khuôn hệt
 * ContentOutlinesPermissionSeeder: platform_content_editor/platform_content_head/
 * platform_section_editor, seed trực tiếp permission riêng module (KHÔNG qua
 * config/permissions.php Lớp B).
 *
 * Chạy: php artisan db:seed --class="Modules\PromptFrameworkStudio\Database\Seeders\PromptFrameworkStudioPermissionSeeder"
 */
class PromptFrameworkStudioPermissionSeeder extends Seeder
{
    private const PERMISSIONS = ['prompt_framework_studio.use'];

    private const ROLES_WITH_ACCESS = [
        'platform_content_editor',
        'platform_content_head',
        'platform_section_editor',
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        foreach (self::ROLES_WITH_ACCESS as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo('prompt_framework_studio.use');
            }
        }

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ PromptFrameworkStudio permissions seeded — platform_content_editor/platform_content_head/platform_section_editor.');
    }
}

<?php

namespace Modules\AIVideoStudioTemplate\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * spec/AIVideoStudioTemplate_Technical_Specification.md §5 — công cụ nội bộ cho team content, cùng
 * nhóm chính xác với ContentOutlinesPermissionSeeder/CoreIdeaExtractorPermissionSeeder:
 * platform_content_editor/platform_content_head/platform_section_editor, seed trực tiếp permission
 * riêng module.
 *
 * Chạy: php artisan db:seed --class="Modules\AIVideoStudioTemplate\Database\Seeders\AIVideoStudioTemplatePermissionSeeder"
 */
class AIVideoStudioTemplatePermissionSeeder extends Seeder
{
    private const PERMISSIONS = ['ai_video_studio_template.use'];

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
                $role->givePermissionTo('ai_video_studio_template.use');
            }
        }

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ AIVideoStudioTemplate permissions seeded — platform_content_editor/platform_content_head/platform_section_editor.');
    }
}

<?php

namespace Modules\ContentOutlines\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * spec/ContentOutlines_Technical_Specification.md §6 — công cụ soạn prompt research nội dung
 * phục vụ người VIẾT bài, cùng nhóm chính xác với CoreIdeaExtractorPermissionSeeder/
 * VideoIdeaExtractorPermissionSeeder: platform_content_editor/platform_content_head/
 * platform_section_editor, seed trực tiếp permission riêng module (KHÔNG qua config/permissions.php
 * Lớp B).
 *
 * Chạy: php artisan db:seed --class="Modules\ContentOutlines\Database\Seeders\ContentOutlinesPermissionSeeder"
 */
class ContentOutlinesPermissionSeeder extends Seeder
{
    private const PERMISSIONS = ['content_outlines.use'];

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
                $role->givePermissionTo('content_outlines.use');
            }
        }

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ ContentOutlines permissions seeded — platform_content_editor/platform_content_head/platform_section_editor.');
    }
}

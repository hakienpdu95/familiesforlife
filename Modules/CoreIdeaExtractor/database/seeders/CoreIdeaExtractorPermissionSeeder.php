<?php

namespace Modules\CoreIdeaExtractor\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * spec/CoreIdeaExtractor.md — công cụ nghiên cứu nội dung phục vụ người VIẾT bài, nên gán cho
 * platform_content_editor (role viết bài thật — xem Modules/Approval/database/seeders/
 * ContentReviewHierarchySeeder.php) + platform_content_head, cùng nguyên tắc BANNER_MANAGE/
 * OCOP_MANAGE/PAGE_MANAGE (permission riêng module, seed trực tiếp — KHÔNG qua
 * config/permissions.php Lớp B).
 *
 * §12 (v1.4) — thêm platform_section_editor: từ khi có Category Content Foundation (họ chỉ sửa
 * được foundation của category mình phụ trách, xem CoreIdeaExtractorServiceProvider::boot()),
 * họ cần tự mở được module này chứ không chỉ content_editor/content_head.
 *
 * Chạy: php artisan db:seed --class="Modules\CoreIdeaExtractor\Database\Seeders\CoreIdeaExtractorPermissionSeeder"
 */
class CoreIdeaExtractorPermissionSeeder extends Seeder
{
    private const PERMISSIONS = ['core_idea_extractor.use'];

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
                'name'       => $name,
                'guard_name' => 'web',
            ]);
        }

        foreach (self::ROLES_WITH_ACCESS as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo('core_idea_extractor.use');
            }
        }

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ CoreIdeaExtractor permissions seeded — platform_content_editor/platform_content_head/platform_section_editor.');
    }
}

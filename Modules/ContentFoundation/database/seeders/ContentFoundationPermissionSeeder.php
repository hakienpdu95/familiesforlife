<?php

namespace Modules\ContentFoundation\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * spec/CoreIdeaExtractor.md §12 — ngữ cảnh biên tập dùng chung bởi mọi công cụ nghiên cứu ý
 * tưởng nội dung, nên gán cho đúng 3 role đã dùng module CoreIdeaExtractor từ trước
 * (platform_content_editor/platform_content_head/platform_section_editor) — cùng nguyên tắc seed
 * permission riêng module, không qua config/permissions.php Lớp B.
 *
 * Chạy: php artisan db:seed --class="Modules\ContentFoundation\Database\Seeders\ContentFoundationPermissionSeeder"
 */
class ContentFoundationPermissionSeeder extends Seeder
{
    private const PERMISSIONS = ['content_foundation.use'];

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
                $role->givePermissionTo('content_foundation.use');
            }
        }

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ ContentFoundation permissions seeded — platform_content_editor/platform_content_head/platform_section_editor.');
    }
}

<?php

namespace Modules\BulkIdeationPromptStudio\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Cùng nguyên tắc VideoSeriesPromptStudioPermissionSeeder — công cụ nghiên cứu/lên kế hoạch nội
 * dung phục vụ đội biên tập, gán cho đúng 3 role đã dùng CoreIdeaExtractor/VideoIdeaExtractor.
 *
 * Chạy: php artisan db:seed --class="Modules\BulkIdeationPromptStudio\Database\Seeders\BulkIdeationPromptStudioPermissionSeeder"
 */
class BulkIdeationPromptStudioPermissionSeeder extends Seeder
{
    private const PERMISSIONS = ['bulk_ideation_prompt_studio.use'];

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
                $role->givePermissionTo('bulk_ideation_prompt_studio.use');
            }
        }

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ BulkIdeationPromptStudio permissions seeded — platform_content_editor/platform_content_head/platform_section_editor.');
    }
}

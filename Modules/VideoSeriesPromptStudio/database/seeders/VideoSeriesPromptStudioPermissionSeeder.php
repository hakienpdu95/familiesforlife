<?php

namespace Modules\VideoSeriesPromptStudio\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Cùng nguyên tắc VideoIdeaExtractorPermissionSeeder — công cụ nghiên cứu/lên kế hoạch nội dung
 * phục vụ người làm video, gán cho đúng 3 role đã dùng CoreIdeaExtractor/VideoIdeaExtractor.
 *
 * Chạy: php artisan db:seed --class="Modules\VideoSeriesPromptStudio\Database\Seeders\VideoSeriesPromptStudioPermissionSeeder"
 */
class VideoSeriesPromptStudioPermissionSeeder extends Seeder
{
    private const PERMISSIONS = ['video_series_prompt_studio.use'];

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
                $role->givePermissionTo('video_series_prompt_studio.use');
            }
        }

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ VideoSeriesPromptStudio permissions seeded — platform_content_editor/platform_content_head/platform_section_editor.');
    }
}

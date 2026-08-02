<?php

namespace Modules\VideoIdeaExtractor\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Cùng nguyên tắc CoreIdeaExtractorPermissionSeeder — công cụ nghiên cứu nội dung phục vụ người
 * làm video, gán cho đúng 3 role đã dùng CoreIdeaExtractor/ContentFoundation.
 *
 * Chạy: php artisan db:seed --class="Modules\VideoIdeaExtractor\Database\Seeders\VideoIdeaExtractorPermissionSeeder"
 */
class VideoIdeaExtractorPermissionSeeder extends Seeder
{
    private const PERMISSIONS = ['video_idea_extractor.use'];

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
                $role->givePermissionTo('video_idea_extractor.use');
            }
        }

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ VideoIdeaExtractor permissions seeded — platform_content_editor/platform_content_head/platform_section_editor.');
    }
}

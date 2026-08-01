<?php

namespace Modules\ContentCalendar\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * spec/ContentCalendar_Technical_Specification.md §13 — cùng khuôn chính xác với
 * AicemPermissionSeeder/CoreIdeaExtractorPermissionSeeder: seed trực tiếp vào role Lớp A
 * (platform_*), KHÔNG qua config/permissions.php (file đó chỉ map Lớp B).
 *
 * Chạy: php artisan db:seed --class="Modules\ContentCalendar\Database\Seeders\ContentCalendarPermissionSeeder"
 */
class ContentCalendarPermissionSeeder extends Seeder
{
    private const PERMISSIONS = ['content_calendar.view', 'content_calendar.manage'];

    private const ROLE_MAP = [
        'platform_content_creator' => ['content_calendar.view', 'content_calendar.manage'],
        'platform_section_editor'  => ['content_calendar.view', 'content_calendar.manage'],
        'platform_content_editor'  => ['content_calendar.view', 'content_calendar.manage'],
        'platform_content_head'    => ['content_calendar.view', 'content_calendar.manage'],
        'platform_viewer'          => ['content_calendar.view'],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (self::ROLE_MAP as $roleName => $perms) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo($perms);
            }
        }

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ ContentCalendar permissions seeded — platform_content_creator/section_editor/content_editor/content_head/viewer.');
    }
}

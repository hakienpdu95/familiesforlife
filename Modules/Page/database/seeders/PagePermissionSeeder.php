<?php

namespace Modules\Page\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * spec/Page_Static_Pages_Technical_Specification.md §7 — Page là tài sản nền tảng, cùng mô
 * hình Banner/Ocop: chỉ role nền tảng thao tác. platform_ops + platform_content_head — không
 * qua config/permissions.php (Lớp B), cùng nguyên tắc BANNER_MANAGE/OCOP_MANAGE.
 *
 * Chạy: php artisan db:seed --class="Modules\Page\Database\Seeders\PagePermissionSeeder"
 */
class PagePermissionSeeder extends Seeder
{
    private const PERMISSIONS = ['page.manage'];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        Role::where('name', 'platform_ops')->where('guard_name', 'web')->first()
            ?->givePermissionTo('page.manage');
        Role::where('name', 'platform_content_head')->where('guard_name', 'web')->first()
            ?->givePermissionTo('page.manage');

        Role::where('name', 'super-admin')->where('guard_name', 'web')->first()
            ?->syncPermissions(Permission::all());

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ Page permissions seeded — platform_ops/platform_content_head.');
    }
}

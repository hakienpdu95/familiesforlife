<?php

namespace Modules\Heritage\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * spec/Heritage_Technical_Specification.md §4 — Heritage là tài sản nền tảng, cùng mô hình
 * Ocop/Banner: platform_ops (vận hành) + platform_content_head (toàn quyền nội dung nền tảng).
 *
 * Chạy: php artisan db:seed --class="Modules\Heritage\Database\Seeders\HeritagePermissionSeeder"
 */
class HeritagePermissionSeeder extends Seeder
{
    private const PERMISSIONS = ['heritage.manage'];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        $ops = Role::where('name', 'platform_ops')->where('guard_name', 'web')->first();
        if ($ops) {
            $ops->givePermissionTo('heritage.manage');
        }

        $head = Role::where('name', 'platform_content_head')->where('guard_name', 'web')->first();
        if ($head) {
            $head->givePermissionTo('heritage.manage');
        }

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ Heritage permissions seeded — platform_ops/platform_content_head.');
    }
}

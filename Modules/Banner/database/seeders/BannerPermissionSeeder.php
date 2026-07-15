<?php

namespace Modules\Banner\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * spec/Banner_Management_Technical_Specification.md §6.3 — Banner là tài sản nền tảng, cùng
 * mô hình Post/Event: chỉ role nền tảng thao tác. platform_ops (vận hành/đối tác quảng cáo) +
 * platform_content_head (toàn quyền nội dung nền tảng) — không có platform_content_creator vì
 * banner không phải nội dung biên tập (không qua quy trình duyệt).
 *
 * Chạy: php artisan db:seed --class="Modules\Banner\Database\Seeders\BannerPermissionSeeder"
 */
class BannerPermissionSeeder extends Seeder
{
    private const PERMISSIONS = ['banner.manage'];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate([
                'name'       => $name,
                'guard_name' => 'web',
            ]);
        }

        $ops = Role::where('name', 'platform_ops')->where('guard_name', 'web')->first();
        if ($ops) {
            $ops->givePermissionTo('banner.manage');
        }

        $head = Role::where('name', 'platform_content_head')->where('guard_name', 'web')->first();
        if ($head) {
            $head->givePermissionTo('banner.manage');
        }

        // super-admin: sync toàn bộ permissions (bao gồm permissions mới)
        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ Banner permissions seeded — platform_ops/platform_content_head.');
    }
}

<?php

namespace Modules\Ocop\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * spec/Province_Showcase_Technical_Specification.md §6.1 — OCOP là tài sản nền tảng, cùng mô
 * hình Banner: platform_ops (vận hành) + platform_content_head (toàn quyền nội dung nền tảng).
 *
 * Chạy: php artisan db:seed --class="Modules\Ocop\Database\Seeders\OcopPermissionSeeder"
 */
class OcopPermissionSeeder extends Seeder
{
    private const PERMISSIONS = ['ocop.manage'];

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
            $ops->givePermissionTo('ocop.manage');
        }

        $head = Role::where('name', 'platform_content_head')->where('guard_name', 'web')->first();
        if ($head) {
            $head->givePermissionTo('ocop.manage');
        }

        // super-admin: sync toàn bộ permissions (bao gồm permissions mới)
        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ Ocop permissions seeded — platform_ops/platform_content_head.');
    }
}

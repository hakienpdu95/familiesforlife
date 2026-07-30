<?php

namespace Modules\AccessTrade\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * AccessTrade là tài sản nền tảng (dữ liệu đồng bộ dùng chung, không thuộc Organization nào) —
 * cùng mô hình Banner/Ocop/Page: chỉ role nền tảng thao tác, KHÔNG qua config/permissions.php
 * (Lớp B). platform_ops (vận hành đồng bộ/đối tác affiliate) + platform_content_head (toàn
 * quyền nội dung nền tảng).
 *
 * Chạy: php artisan db:seed --class="Modules\AccessTrade\Database\Seeders\AccessTradePermissionSeeder"
 */
class AccessTradePermissionSeeder extends Seeder
{
    private const PERMISSIONS = ['accesstrade.manage'];

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
            $ops->givePermissionTo('accesstrade.manage');
        }

        $head = Role::where('name', 'platform_content_head')->where('guard_name', 'web')->first();
        if ($head) {
            $head->givePermissionTo('accesstrade.manage');
        }

        // super-admin: sync toàn bộ permissions (bao gồm permissions mới)
        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ AccessTrade permissions seeded — platform_ops/platform_content_head.');
    }
}

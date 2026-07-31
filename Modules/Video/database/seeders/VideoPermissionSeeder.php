<?php

namespace Modules\Video\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * spec/Video_Management_Technical_Specification.md §6.7 — Video là tài sản nền tảng, cùng mô
 * hình Banner/Page: chỉ role nền tảng thao tác. platform_ops (vận hành) + platform_content_head
 * (toàn quyền nội dung nền tảng) — không có platform_content_creator vì video không phải nội
 * dung biên tập (không qua quy trình duyệt).
 *
 * Chạy: php artisan db:seed --class="Modules\Video\Database\Seeders\VideoPermissionSeeder"
 */
class VideoPermissionSeeder extends Seeder
{
    private const PERMISSIONS = ['video.manage'];

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
            $ops->givePermissionTo('video.manage');
        }

        $head = Role::where('name', 'platform_content_head')->where('guard_name', 'web')->first();
        if ($head) {
            $head->givePermissionTo('video.manage');
        }

        // super-admin: sync toàn bộ permissions (bao gồm permissions mới)
        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ Video permissions seeded — platform_ops/platform_content_head.');
    }
}

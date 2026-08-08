<?php

namespace Modules\Playlist\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * spec/Playlist_Technical_Specification.md §0/§6.5 — Playlist là tài sản nền tảng, cùng mô
 * hình Video/Banner: chỉ role nền tảng thao tác. platform_ops (vận hành) + platform_content_head
 * (toàn quyền nội dung nền tảng) — không có platform_content_creator vì playlist không phải nội
 * dung biên tập (không qua quy trình duyệt).
 *
 * Chạy: php artisan db:seed --class="Modules\Playlist\Database\Seeders\PlaylistPermissionSeeder"
 */
class PlaylistPermissionSeeder extends Seeder
{
    private const PERMISSIONS = ['playlist.manage'];

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
            $ops->givePermissionTo('playlist.manage');
        }

        $head = Role::where('name', 'platform_content_head')->where('guard_name', 'web')->first();
        if ($head) {
            $head->givePermissionTo('playlist.manage');
        }

        // super-admin: sync toàn bộ permissions (bao gồm permissions mới)
        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ Playlist permissions seeded — platform_ops/platform_content_head.');
    }
}

<?php

namespace Modules\Post\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * spec/Breaking_News_Ticker_Technical_Specification.md §6.3 — tin nóng là quyết định biên
 * tập/vận hành nhanh, gán cho platform_ops (vận hành) + platform_content_head (toàn quyền nội
 * dung nền tảng), KHÔNG qua config/permissions.php (Lớp B) — cùng nguyên tắc BannerPermissionSeeder.
 *
 * Chạy: php artisan db:seed --class="Modules\Post\Database\Seeders\BreakingNewsPermissionSeeder"
 */
class BreakingNewsPermissionSeeder extends Seeder
{
    private const PERMISSIONS = ['breaking_news.manage'];

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
            $ops->givePermissionTo('breaking_news.manage');
        }

        $head = Role::where('name', 'platform_content_head')->where('guard_name', 'web')->first();
        if ($head) {
            $head->givePermissionTo('breaking_news.manage');
        }

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ BreakingNews permissions seeded — platform_ops/platform_content_head.');
    }
}

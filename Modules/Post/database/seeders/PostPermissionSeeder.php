<?php

namespace Modules\Post\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seed các permission post_article.view/create/edit/delete/publish + post_category.manage
 * và gán vào role phù hợp (docs/post-module-spec.md §10).
 * Chạy: php artisan db:seed --class="Modules\Post\Database\Seeders\PostPermissionSeeder"
 */
class PostPermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'post_category.manage',
        'post_article.view',
        'post_article.create',
        'post_article.edit',
        'post_article.delete',
        'post_article.publish',
        'post_article.unpublish',
    ];

    private const ROLE_MAP = [
        'ceo' => [
            'post_article.view',
            'post_article.publish',
            'post_article.unpublish',
        ],
        'sales' => [
            'post_article.view',
        ],
        'ops' => [
            'post_article.view',
            'post_article.publish',
        ],
        'marketing' => [
            'post_article.view',
            'post_article.create',
            'post_article.edit',
            'post_article.delete',
        ],
        'hr' => [
            'post_article.view',
        ],
        'ai_operator' => [
            'post_article.view',
        ],
        'viewer' => [
            'post_article.view',
        ],
        'system_admin' => [
            'post_article.view',
            'post_article.create',
            'post_article.edit',
            'post_article.delete',
            'post_article.publish',
            'post_article.unpublish',
            'post_category.manage',
        ],
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

        foreach (self::ROLE_MAP as $roleName => $perms) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo($perms);
            }
        }

        // super-admin: sync toàn bộ permissions (bao gồm permissions mới)
        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ Post permissions seeded.');
    }
}

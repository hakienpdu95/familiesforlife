<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seed các permission product.view/product.create/product.edit/product.delete/
 * product_category.manage và gán vào role phù hợp (docs/product-catalog-spec.md §8).
 *
 * KHÔNG có product.publish — duyệt/xuất bản nội dung sản phẩm giờ do đội kiểm duyệt tập
 * trung của Hà Kiên xử lý (role platform_content_moderator, xem ProductPolicy::approve/reject/
 * publishApproval/archiveApproval), không phải permission của tài khoản doanh nghiệp.
 *
 * Chạy: php artisan db:seed --class="Modules\Product\Database\Seeders\ProductPermissionSeeder"
 */
class ProductPermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'product_category.manage',
        'product.view',
        'product.create',
        'product.edit',
        'product.delete',
    ];

    private const ROLE_MAP = [
        'ceo' => [
            'product.view',
            'product.create',
            'product.edit',
        ],
        'sales' => [
            'product.view',
            'product.create',
            'product.edit',
        ],
        'ops' => [
            'product.view',
            'product.create',
            'product.edit',
        ],
        'marketing' => [
            'product.view',
            'product.create',
            'product.edit',
        ],
        'hr' => [
            'product.view',
        ],
        'ai_operator' => [
            'product.view',
        ],
        'viewer' => [
            'product.view',
        ],
        'system_admin' => [
            'product.view',
            'product.create',
            'product.edit',
            'product.delete',
            'product_category.manage',
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

        $this->command->info('  ✓ Product permissions seeded.');
    }
}

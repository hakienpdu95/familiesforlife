<?php

namespace Modules\OcopRubric\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seed các permission ocop_rubric.manage / ocop_product.view / ocop_product.manage
 * / ocop_practice.use / ocop_self_assess.use và gán vào role phù hợp.
 * Chạy: php artisan db:seed --class="Modules\OcopRubric\Database\Seeders\OcopRubricPermissionSeeder"
 */
class OcopRubricPermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'ocop_rubric.manage',
        'ocop_product.view',
        'ocop_product.manage',
        'ocop_practice.use',
        'ocop_self_assess.use',
    ];

    private const ROLE_MAP = [
        // System Admin: chỉ quản lý bộ tiêu chí (quy định pháp luật cố định, §4/§11 spec)
        // — KHÔNG có ocop_product.*/ocop_practice.* vì đó là dữ liệu kinh doanh của tổ chức, ngoài phạm vi Admin.
        'system_admin' => [
            'ocop_rubric.manage',
        ],
        // CEO: quản lý sản phẩm + luyện tập + tự đánh giá (self-assessment là trách nhiệm hồ sơ pháp lý)
        'ceo' => [
            'ocop_product.view',
            'ocop_product.manage',
            'ocop_practice.use',
            'ocop_self_assess.use',
        ],
        // Ops: quản lý sản phẩm + luyện tập, KHÔNG tự đánh giá
        'ops' => [
            'ocop_product.view',
            'ocop_product.manage',
            'ocop_practice.use',
        ],
        // HR: chỉ luyện tập, không sửa sản phẩm/tự đánh giá
        'hr' => [
            'ocop_practice.use',
        ],
        'viewer' => [
            'ocop_product.view',
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

        $this->command->info('  ✓ OcopRubric permissions seeded.');
    }
}

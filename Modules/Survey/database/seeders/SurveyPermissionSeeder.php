<?php

namespace Modules\Survey\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seed 7 permissions survey.* và gán vào các role phù hợp.
 * Chạy: php artisan db:seed --class="Modules\Survey\Database\Seeders\SurveyPermissionSeeder"
 */
class SurveyPermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'survey.view',
        'survey.create',
        'survey.update',
        'survey.delete',
        'survey.manage_tokens',
        'survey.view_responses',
        'survey.export',
    ];

    private const ROLE_MAP = [
        // System Admin: full access
        'system_admin' => [
            'survey.view',
            'survey.create',
            'survey.update',
            'survey.delete',
            'survey.manage_tokens',
            'survey.view_responses',
            'survey.export',
        ],
        // CEO: view + responses + export
        'ceo' => [
            'survey.view',
            'survey.view_responses',
            'survey.export',
        ],
        // Marketing: full management (surveys dùng cho marketing)
        'marketing' => [
            'survey.view',
            'survey.create',
            'survey.update',
            'survey.manage_tokens',
            'survey.view_responses',
            'survey.export',
        ],
        // Ops: view + responses
        'ops' => [
            'survey.view',
            'survey.view_responses',
        ],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Tạo permissions nếu chưa có
        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate([
                'name'       => $name,
                'guard_name' => 'web',
            ]);
        }

        // Gán vào roles
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

        $this->command->info('  ✓ Survey permissions seeded.');
    }
}

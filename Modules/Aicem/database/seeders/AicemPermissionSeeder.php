<?php

namespace Modules\Aicem\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seed 4 permission aicem.view/use/config_prompt/config và gán vào role phù hợp
 * (spec/AICEM_Technical_Specification.md mục 12). Chạy 1 lần cho toàn hệ thống, không lặp
 * theo Organization (khác SeedDefaultKnowledgeBaseAction — Phase 2 — chạy theo từng Organization).
 * Chạy: php artisan db:seed --class="Modules\Aicem\Database\Seeders\AicemPermissionSeeder"
 */
class AicemPermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'aicem.view',
        'aicem.use',
        'aicem.config_prompt',
        'aicem.config',
    ];

    private const ROLE_MAP = [
        'ceo' => [
            'aicem.view',
        ],
        'sales' => [
            // Không liên quan biên soạn bài — no permission
        ],
        'ops' => [
            'aicem.view',
        ],
        'marketing' => [
            'aicem.use',
        ],
        'hr' => [
            // Không liên quan biên soạn bài — no permission
        ],
        'ai_operator' => [
            'aicem.config_prompt',
        ],
        'viewer' => [
            // Không liên quan biên soạn bài — no permission
        ],
        'system_admin' => [
            'aicem.view',
            'aicem.use',
            'aicem.config_prompt',
            'aicem.config',
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
            if (empty($perms)) {
                continue;
            }

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

        $this->command->info('  ✓ Aicem permissions seeded.');
    }
}

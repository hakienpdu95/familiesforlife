<?php

namespace Modules\ContentBrief\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * spec/ContentBrief_Technical_Specification.md §5 — Lớp A: gán trực tiếp cho 8 role gốc, cùng
 * cơ chế thật đang dùng bởi Modules\Aicem\Database\Seeders\AicemPermissionSeeder (KHÔNG phải
 * config/permissions.php — file đó hiện không có command nào thật sự đọc/sync nó).
 *
 * Marketing=Soạn thảo/Gửi duyệt/Yêu cầu sinh nội dung | CEO/Ops=Duyệt | System_Admin=Full
 * | Sales/HR/AI_Operator/Viewer=không truy cập (Content Brief không liên quan tới vai trò đó).
 *
 * Chạy: php artisan db:seed --class="Modules\ContentBrief\Database\Seeders\ContentBriefPermissionSeeder"
 */
class ContentBriefPermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'content_brief.view',
        'content_brief.manage',
        'content_brief.approve',
    ];

    private const ROLE_MAP = [
        'ceo' => [
            'content_brief.view',
            'content_brief.approve',
        ],
        'sales' => [
            // Không liên quan lên kế hoạch nội dung — no permission
        ],
        'ops' => [
            'content_brief.view',
            'content_brief.approve',
        ],
        'marketing' => [
            'content_brief.view',
            'content_brief.manage',
        ],
        'hr' => [
            // Không liên quan — no permission
        ],
        'ai_operator' => [
            // Không liên quan — no permission
        ],
        'viewer' => [
            // Không liên quan — no permission
        ],
        'system_admin' => [
            'content_brief.view',
            'content_brief.manage',
            'content_brief.approve',
        ],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
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

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ Content Brief permissions seeded — ceo/ops (approve), marketing (manage), system_admin (full).');
    }
}

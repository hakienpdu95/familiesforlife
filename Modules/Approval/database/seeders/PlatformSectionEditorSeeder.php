<?php

namespace Modules\Approval\Database\Seeders;

use App\Enums\AccountType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Biên tập viên trưởng chuyên mục — spec/Platform_RBAC_Phase2_Specification.md §4 (v3.0).
 * Duyệt sơ bộ (Submitted → Approved) NHƯNG chỉ giới hạn trong các category được gán qua
 * `post_category_editors` (xem `PostArticlePolicy::approve()`). Việc gán category cụ thể cho
 * từng biên tập viên KHÔNG nằm trong seeder này — làm thủ công qua `post_category_editors`
 * sau khi có danh mục thật (chưa có UI riêng cho việc gán này ở Phase 2A).
 *
 * Tài khoản mặc định: section-editor@system.local (mật khẩu Admin@123!)
 * ⚠️  Đổi mật khẩu ngay sau khi deploy production.
 */
class PlatformSectionEditorSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        setPermissionsTeamId(null);
        $role = Role::firstOrCreate(['name' => 'platform_section_editor', 'guard_name' => 'web']);

        $user = User::withoutGlobalScopes()->firstOrCreate(
            ['email' => 'section-editor@system.local'],
            [
                'name'              => 'Section Editor',
                'password'          => Hash::make('Admin@123!'),
                'organization_id'   => null,
                'email_verified_at' => now(),
                'trust_level'       => 2,
                'account_type'      => AccountType::Platform,
            ]
        );

        if (! $user->wasRecentlyCreated) {
            $user->forceFill(['account_type' => AccountType::Platform])->save();
        }

        setPermissionsTeamId(null);
        $user->syncRoles($role);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ platform_section_editor role seeded (section-editor@system.local).');
    }
}

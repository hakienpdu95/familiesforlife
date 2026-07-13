<?php

namespace Modules\Approval\Database\Seeders;

use App\Enums\AccountType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seed role `content_moderator` — tài khoản kiểm duyệt tập trung của nền tảng (Platform
 * Approval Gateway), KHÔNG thuộc bất kỳ Organization nào (organization_id = null), giống hệt
 * quy ước `super-admin` đã có (Modules/Auth/database/seeders/AuthDatabaseSeeder.php) nhưng
 * hẹp hơn nhiều: chỉ có quyền duyệt/từ chối/xuất bản/lưu trữ nội dung (approve/reject/
 * publishApproval/archiveApproval trong các Policy), KHÔNG bypass Gate toàn cục như
 * super-admin.
 *
 * setPermissionsTeamId(null) gọi TƯỜNG MINH trước assignRole() — không dựa vào "ambient team
 * context tình cờ đang null lúc seed chạy" như AuthDatabaseSeeder đang làm (rủi ro thật nếu
 * thứ tự seeder thay đổi sau này, xem ghi chú trong
 * spec/Workflow_Approval_Technical_Specification.md §17 mở rộng).
 *
 * Tài khoản mặc định: moderator@system.local / Admin@123!
 * ⚠️  Đổi mật khẩu ngay sau khi deploy production.
 */
class ContentModeratorSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        setPermissionsTeamId(null);
        // Đổi tên platform_content_moderator (từ content_moderator) —
        // spec/Platform_RBAC_Technical_Specification.md §3.2/§3.5. Migration
        // 2026_07_13_000003_rename_platform_content_roles đã đổi tên role đã tồn tại trong DB;
        // firstOrCreate() ở đây dùng tên MỚI để không tạo lại role cũ nếu seeder chạy lại
        // (vd cài mới từ đầu, chưa từng có role content_moderator để migration đổi tên).
        $role = Role::firstOrCreate([
            'name'       => 'platform_content_moderator',
            'guard_name' => 'web',
        ]);

        $user = User::withoutGlobalScopes()->firstOrCreate(
            ['email' => 'moderator@system.local'],
            [
                'name'              => 'Content Moderator',
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

        $this->command->info('  ✓ platform_content_moderator role + moderator@system.local seeded.');
    }
}

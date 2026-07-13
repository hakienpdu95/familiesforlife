<?php

namespace Modules\Approval\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Phân cấp 2 tầng duyệt bài viết (Modules/Post) — mô hình toà soạn tin tức thật:
 * "cộng tác viên gửi bài → biên tập viên (content_editor) duyệt sơ bộ → trưởng phòng nội
 * dung (content_head) duyệt cuối trước khi hiển thị ra cổng thông tin"
 * (spec/Workflow_Approval_Technical_Specification.md §18.10).
 *
 * Cả 2 role đều organization_id=null (Platform Approval Gateway, xem ContentModeratorSeeder
 * cho quy ước chung) — content_head làm được cả việc của content_editor
 * (PostArticlePolicy::approve cho phép cả 2 role), không áp dụng ngược.
 *
 * Tài khoản mặc định: editor@system.local, content-head@system.local (mật khẩu Admin@123!)
 * ⚠️  Đổi mật khẩu ngay sau khi deploy production.
 */
class ContentReviewHierarchySeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        setPermissionsTeamId(null);
        // Đổi tên platform_content_editor/platform_content_head (từ content_editor/
        // content_head) — spec/Platform_RBAC_Technical_Specification.md §3.2/§3.5. Dùng tên
        // MỚI ở đây để không tạo lại role cũ nếu seeder chạy lại trên môi trường chưa từng có
        // role cũ (vd cài mới từ đầu).
        $editorRole = Role::firstOrCreate(['name' => 'platform_content_editor', 'guard_name' => 'web']);
        $headRole   = Role::firstOrCreate(['name' => 'platform_content_head', 'guard_name' => 'web']);

        $this->createAccount('editor@system.local', 'Content Editor', $editorRole);
        $this->createAccount('content-head@system.local', 'Content Department Head', $headRole);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ platform_content_editor + platform_content_head roles seeded (editor@system.local, content-head@system.local).');
    }

    private function createAccount(string $email, string $name, Role $role): void
    {
        $user = User::withoutGlobalScopes()->firstOrCreate(
            ['email' => $email],
            [
                'name'              => $name,
                'password'          => Hash::make('Admin@123!'),
                'organization_id'   => null,
                'email_verified_at' => now(),
                'trust_level'       => 2,
            ]
        );

        setPermissionsTeamId(null);
        $user->syncRoles($role);
    }
}

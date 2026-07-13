<?php

namespace Modules\Approval\Database\Seeders;

use App\Enums\AccountType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Role viết bài cho đội biên tập Platform — spec/Platform_RBAC_Phase2_Specification.md §3.1
 * (v3.0). Gộp 2 role dự kiến ban đầu ở bản v2.0 (`platform_reporter` viết bài,
 * `platform_media` chuyên upload ảnh) thành 1 role duy nhất `platform_content_creator`: ranh
 * giới "viết chữ" vs "chỉ upload ảnh" không mang tính kiểm soát rủi ro nên gộp được — khác hẳn
 * ranh giới viết vs duyệt (BẮT BUỘC tách biệt, xem §0 bảng quyết định) để tránh 1 tài khoản
 * vừa viết vừa tự duyệt bài của chính mình. Role này KHÔNG có quyền publish/unpublish —
 * quyền đó vẫn chỉ `platform_content_head` (xác định qua `isPlatformContentHead()`, không
 * qua Spatie permission — xem `PostArticlePolicy`).
 *
 * Tài khoản mặc định: content-creator@system.local (mật khẩu Admin@123!)
 * ⚠️  Đổi mật khẩu ngay sau khi deploy production.
 */
class PlatformContentCreatorSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        setPermissionsTeamId(null);
        $role = Role::firstOrCreate(['name' => 'platform_content_creator', 'guard_name' => 'web']);

        $this->createAccount('content-creator@system.local', 'Content Creator', $role);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ platform_content_creator role seeded (content-creator@system.local).');
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
                'account_type'      => AccountType::Platform,
            ]
        );

        if (! $user->wasRecentlyCreated) {
            $user->forceFill(['account_type' => AccountType::Platform])->save();
        }

        setPermissionsTeamId(null);
        $user->syncRoles($role);
    }
}

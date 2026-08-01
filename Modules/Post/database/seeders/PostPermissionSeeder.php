<?php

namespace Modules\Post\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * spec/Platform_RBAC_Phase2_Specification.md §3.2 (v3.0) — Post là tài sản của nền tảng.
 * Thu hồi TOÀN BỘ permission Post (kể cả view) khỏi 8 role Lớp B (doanh nghiệp không còn
 * thao tác/xem Post ở dashboard nữa). Cấp create/edit/delete + post_media.upload (permission
 * mới) cho role Platform mới `platform_content_creator`; publish/unpublish vẫn chỉ
 * `platform_content_head` — xác định qua `isPlatformContentHead()` (global role), KHÔNG qua
 * Spatie permission, nên không role nào cần permission `post_article.publish`/`unpublish` nữa.
 *
 * Chạy: php artisan db:seed --class="Modules\Post\Database\Seeders\PostPermissionSeeder"
 */
class PostPermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'post_category.manage',
        'post_tag.manage',
        'post_article.view',
        'post_article.create',
        'post_article.edit',
        'post_article.delete',
        'post_article.publish',
        'post_article.unpublish',
        'post_article.manage_sponsorship',
        'post_media.upload',
        'post_analytics.view',
    ];

    /** 8 role Lớp B — thu hồi toàn bộ permission Post đã cấp trước đây (không chỉ ngừng cấp mới, mà revoke tường minh permission cũ). */
    private const LOP_B_ROLES = [
        'ceo', 'sales', 'ops', 'marketing', 'hr', 'ai_operator', 'viewer', 'system_admin',
    ];

    private const LOP_B_POST_PERMISSIONS = [
        'post_category.manage',
        'post_article.view',
        'post_article.create',
        'post_article.edit',
        'post_article.delete',
        'post_article.publish',
        'post_article.unpublish',
        'post_article.manage_sponsorship',
    ];

    /** `platform_content_creator` — viết/sửa bài + upload media + tự set sponsor khi viết bài tài trợ. KHÔNG publish/unpublish. */
    private const PLATFORM_CONTENT_CREATOR_PERMISSIONS = [
        'post_article.create',
        'post_article.edit',
        'post_article.delete',
        'post_article.manage_sponsorship',
        'post_media.upload',
    ];

    /**
     * `platform_content_head` — trước đây KHÔNG có bất kỳ Spatie permission nào từ seeder này
     * (publish/unpublish kiểm tra qua isPlatformContentHead() trực tiếp trong code, không qua
     * Spatie permission — xem comment đầu file). Nhưng PostCategoryPolicy/PostTagPolicy dùng
     * chuẩn $user->can('post_category.manage'|'post_tag.manage'), nên CẦN permission Spatie
     * thật ở đây — spec/PostTag_Management_Technical_Specification.md §5.1: đổi tên/gộp/xoá
     * tag-category ảnh hưởng nhiều bài viết cùng lúc, cùng cấp độ với quyền publish/unpublish
     * đã thuộc platform_content_head. Đồng thời sửa gap cũ: post_category.manage trước đây
     * chỉ super-admin có (§4 spec trên), chưa role Platform nào quản lý được category qua UI.
     */
    private const PLATFORM_CONTENT_HEAD_PERMISSIONS = [
        'post_category.manage',
        'post_tag.manage',
    ];

    /**
     * spec/ga-dashboard-statistics.md §1 — trang "Thống kê traffic" (GA4), tính năng CHỈ ĐỌC.
     * Cấp rộng cho đội biên tập + vận hành; KHÔNG cấp `platform_content_creator`/
     * `platform_content_moderator` (không cần xem traffic để viết/duyệt bài), và KHÔNG cấp 8
     * role Lớp B (Post là tài sản nền tảng, xem đầu file).
     */
    private const ANALYTICS_VIEWER_ROLES = [
        'platform_content_editor',
        'platform_content_head',
        'platform_section_editor',
        'platform_ops',
        'platform_viewer',
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

        foreach (self::LOP_B_ROLES as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->revokePermissionTo(self::LOP_B_POST_PERMISSIONS);
            }
        }

        $contentCreator = Role::where('name', 'platform_content_creator')->where('guard_name', 'web')->first();
        if ($contentCreator) {
            $contentCreator->givePermissionTo(self::PLATFORM_CONTENT_CREATOR_PERMISSIONS);
        }

        $contentHead = Role::where('name', 'platform_content_head')->where('guard_name', 'web')->first();
        if ($contentHead) {
            $contentHead->givePermissionTo(self::PLATFORM_CONTENT_HEAD_PERMISSIONS);
        }

        foreach (self::ANALYTICS_VIEWER_ROLES as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo('post_analytics.view');
            }
        }

        // super-admin: sync toàn bộ permissions (bao gồm permissions mới)
        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ Post permissions seeded — thu hồi khỏi Lớp B, cấp cho platform_content_creator/platform_content_head/nhóm xem thống kê traffic.');
    }
}

<?php

namespace Modules\Event\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * spec/Event_Management_Technical_Specification.md §9 — Event là tài sản nền tảng (không
 * organization_id), cùng mô hình Post: chỉ 3 role nền tảng thao tác (platform_content_editor,
 * platform_content_head, platform_ops), KHÔNG role Lớp B (CEO/Sales/Ops/...) nào cần permission
 * Event — không có bước "thu hồi khỏi Lớp B" như PostPermissionSeeder vì Event chưa từng cấp
 * cho role nào khác trước đây (module mới hoàn toàn, không có permission cũ cần dọn).
 *
 * event.moderate/event.publish/event.unpublish được tạo ở đây để tài liệu hoá đầy đủ hành
 * động tồn tại, nhưng KHÔNG gán cho role nào — EventPolicy::approve()/publish()/archive() dùng
 * role-helper (isPlatformContentEditor/Head) trực tiếp, không qua permission string (cùng
 * nguyên tắc PostArticlePolicy, xem EventPolicy).
 *
 * Chạy: php artisan db:seed --class="Modules\Event\Database\Seeders\EventPermissionSeeder"
 */
class EventPermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'event_category.manage',
        'event.view',
        'event.edit',
        'event.moderate',
        'event.publish',
        'event.unpublish',
        'event.delete',
    ];

    /** platform_content_editor — sơ duyệt (§6.1: có event.edit để chuẩn hoá nội dung trước Approve). */
    private const PLATFORM_CONTENT_EDITOR_PERMISSIONS = [
        'event.view',
        'event.edit',
        'event_category.manage',
    ];

    /** platform_content_head — toàn quyền editor + publish/unpublish/delete. */
    private const PLATFORM_CONTENT_HEAD_PERMISSIONS = [
        'event.view',
        'event.edit',
        'event_category.manage',
        'event.publish',
        'event.unpublish',
        'event.delete',
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

        $editor = Role::where('name', 'platform_content_editor')->where('guard_name', 'web')->first();
        if ($editor) {
            $editor->givePermissionTo(self::PLATFORM_CONTENT_EDITOR_PERMISSIONS);
        }

        $head = Role::where('name', 'platform_content_head')->where('guard_name', 'web')->first();
        if ($head) {
            $head->givePermissionTo(self::PLATFORM_CONTENT_HEAD_PERMISSIONS);
        }

        // platform_ops — được thêm/sửa sự kiện trực tiếp (không qua form public), thường là
        // người trực tiếp làm việc với đối tác/địa điểm. KHÔNG có event.publish/unpublish/delete/
        // event_category.manage — vẫn không tự duyệt/xuất bản được sự kiện của chính mình
        // (EventPolicy::approve()/publish() chỉ role-helper editor/head, xem EventPolicy).
        $ops = Role::where('name', 'platform_ops')->where('guard_name', 'web')->first();
        if ($ops) {
            $ops->givePermissionTo(['event.view', 'event.edit']);
        }

        // super-admin: sync toàn bộ permissions (bao gồm permissions mới)
        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('  ✓ Event permissions seeded — platform_content_editor/head/ops.');
    }
}

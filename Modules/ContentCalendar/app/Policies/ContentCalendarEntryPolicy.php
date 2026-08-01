<?php

namespace Modules\ContentCalendar\Policies;

use App\Models\User;
use Modules\ContentCalendar\Models\ContentCalendarEntry;

/**
 * spec/ContentCalendar_Technical_Specification.md §6.3. Đăng ký qua Gate::policy() trong
 * ContentCalendarServiceProvider::boot() — module này có model riêng nên không cần Gate::define()
 * ability rời như CoreIdeaExtractorServiceProvider (module đó không có model riêng thời điểm viết).
 */
class ContentCalendarEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('content_calendar.view');
    }

    public function view(User $user, ContentCalendarEntry $entry): bool
    {
        if (! $user->can('content_calendar.view')) {
            return false;
        }

        if ($user->isPlatformContentEditor() || $user->isPlatformContentHead()) {
            return true;
        }

        // platform_viewer là role read-only ĐẦU TIÊN ở Lớp A (spec/Platform_RBAC_Technical_
        // Specification.md §3.3) — nghĩa là xem được TOÀN BỘ board (không viết được, do
        // Policy::update()/create()/delete() đòi content_calendar.manage mà role này không có),
        // KHÔNG PHẢI chỉ xem đúng entry của họ (họ không tạo/được gán entry nào cả). Bắt buộc có
        // nhánh riêng ở đây — nếu rơi xuống điều kiện ownership bên dưới, platform_viewer sẽ
        // không thấy entry nào, ngược với ý nghĩa "giám sát toàn cục" của role.
        if ($user->isPlatformViewer()) {
            return true;
        }

        if ($user->isPlatformSectionEditor()) {
            return $user->postCategoryEditorships()->where('post_categories.id', $entry->post_category_id)->exists();
        }

        return $entry->created_by === $user->id || $entry->assigned_to === $user->id;
    }

    public function create(User $user): bool
    {
        // Phạm vi category cụ thể (§6.4) được CreateCalendarEntryAction tự kiểm tra thêm — Policy
        // ở đây chỉ trả lời "có được tạo entry NÓI CHUNG không", vì tại thời điểm gọi create()
        // (trước khi entry tồn tại) chưa có post_category_id nào để Policy kiểm tra phạm vi.
        return $user->can('content_calendar.manage');
    }

    public function update(User $user, ContentCalendarEntry $entry): bool
    {
        return $this->view($user, $entry) && $user->can('content_calendar.manage');
    }

    /** Xoá: chỉ editor/head, hoặc chủ entry khi CHƯA liên kết bài viết thật (§6.3). */
    public function delete(User $user, ContentCalendarEntry $entry): bool
    {
        if (! $user->can('content_calendar.manage')) {
            return false;
        }

        if ($user->isPlatformContentEditor() || $user->isPlatformContentHead()) {
            return true;
        }

        return $entry->post_article_id === null
            && ($entry->created_by === $user->id || $this->view($user, $entry));
    }
}

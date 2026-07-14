<?php

namespace Modules\Event\Policies;

use App\Models\User;
use Modules\Event\Models\Event;

/**
 * Duyệt sự kiện dùng role-helper trực tiếp (isPlatformContentEditor/isPlatformContentHead),
 * KHÔNG qua permission string — cùng nguyên tắc đã áp dụng cho PostArticlePolicy::approve()/
 * publish()/archive(): tách bạch cứng "ai được duyệt/xuất bản" khỏi hệ permission Spatie
 * thông thường, tránh permission creep vô tình cấp nhầm quyền duyệt qua đường khác. CRUD danh
 * mục (EventCategoryPolicy) vẫn dùng permission string bình thường, cùng pattern
 * PostCategoryPolicy — 2 lớp gate khác nhau cho 2 loại thao tác khác nhau.
 */
class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('event.view')
            || $user->isPlatformContentEditor()
            || $user->isPlatformContentHead()
            || $user->isPlatformOps();
    }

    public function view(User $user, Event $event): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Staff tạo thẳng sự kiện trong dashboard (không qua form public) — Phase 2.
     * platform_ops được thêm vào đây (không chỉ view) — vận hành thường là người trực tiếp
     * làm việc với đối tác/địa điểm nên cần tự nhập sự kiện, KHÔNG chỉ theo dõi. Vẫn KHÔNG có
     * approve()/publish() — giữ nguyên tắc tách biệt "viết" vs "duyệt" (cùng lý do
     * platform_content_creator của Post không tự publish được bài của chính mình).
     */
    public function create(User $user): bool
    {
        return $user->isPlatformContentEditor() || $user->isPlatformContentHead() || $user->isPlatformOps();
    }

    /** UpdateEventAction — sửa nội dung trước Approve (spec §6.1), tách biệt approve()/publish(). */
    public function update(User $user, Event $event): bool
    {
        return $user->isPlatformContentEditor() || $user->isPlatformContentHead() || $user->isPlatformOps();
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->isPlatformContentHead();
    }

    public function approve(User $user, Event $event): bool
    {
        return $user->isPlatformContentEditor() || $user->isPlatformContentHead();
    }

    public function reject(User $user, Event $event): bool
    {
        return $user->isPlatformContentEditor() || $user->isPlatformContentHead();
    }

    public function publish(User $user, Event $event): bool
    {
        return $user->isPlatformContentHead();
    }

    public function archive(User $user, Event $event): bool
    {
        return $user->isPlatformContentHead();
    }
}

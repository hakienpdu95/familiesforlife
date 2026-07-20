<?php

namespace Modules\Newsletter\Policies;

use App\Models\User;

/**
 * spec/Newsletter_Technical_Specification.md §11 — thuần role-helper platform-wide, không có
 * permission Spatie nào mới (§0 mục 10). super-admin bypass qua Gate::before() sẵn có
 * (app/Providers/AppServiceProvider.php).
 */
class NewsletterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformContentEditor() || $user->isPlatformContentHead();
    }

    /** Xoá thủ công (§0 mục 15) — cùng cấp viewAny(), thao tác dọn dẹp danh sách thường ngày. */
    public function removeSubscriber(User $user): bool
    {
        return $this->viewAny($user);
    }

    /** Gửi broadcast tới toàn bộ danh sách — hành động khó thu hồi, chỉ cấp cao hơn. */
    public function sendBroadcast(User $user): bool
    {
        return $user->isPlatformContentHead();
    }
}

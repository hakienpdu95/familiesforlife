<?php

namespace Modules\Post\Features\AuthorHub\Support;

use App\Models\User;

/**
 * spec/Author_Contributor_Hub_Technical_Specification.md §0 v1.2 — điều kiện phạm vi "tác
 * giả" của Author Hub: chỉ `User` do hệ thống/nền tảng quản lý (`account_type=platform`),
 * loại `marketing` (Lớp B, tài khoản do 1 Organization quản lý, §9) dù vẫn viết bài được theo
 * `PostArticlePolicy` hiện hành.
 */
class AuthorRoleResolver
{
    public static function isEligible(User $user): bool
    {
        return $user->isPlatform();
    }
}

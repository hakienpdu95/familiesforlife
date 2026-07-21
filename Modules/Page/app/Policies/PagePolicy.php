<?php

namespace Modules\Page\Policies;

use App\Models\User;
use Modules\Page\Models\Page;

/**
 * spec/Page_Static_Pages_Technical_Specification.md §4.3 — 1 permission page.manage cho mọi
 * hành động (không tách granular như Post — Page không có quy trình duyệt nhiều vai trò).
 */
class PagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('page.manage');
    }

    public function view(User $user, Page $page): bool
    {
        return $user->can('page.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('page.manage');
    }

    public function update(User $user, Page $page): bool
    {
        return $user->can('page.manage');
    }

    public function delete(User $user, Page $page): bool
    {
        return $user->can('page.manage');
    }
}

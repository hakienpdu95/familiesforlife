<?php

namespace Modules\Ocop\Policies;

use App\Models\User;
use Modules\Ocop\Models\OcopCategory;

/**
 * spec/Province_Showcase_Technical_Specification.md §6.1 — 1 permission duy nhất (ocop.manage)
 * cho cả 2 resource (category + product), cùng nguyên tắc BannerPolicy.
 */
class OcopCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ocop.manage');
    }

    public function view(User $user, OcopCategory $category): bool
    {
        return $user->can('ocop.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('ocop.manage');
    }

    public function update(User $user, OcopCategory $category): bool
    {
        return $user->can('ocop.manage');
    }

    public function delete(User $user, OcopCategory $category): bool
    {
        return $user->can('ocop.manage');
    }
}

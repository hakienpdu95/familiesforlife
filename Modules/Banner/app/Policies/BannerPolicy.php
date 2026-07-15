<?php

namespace Modules\Banner\Policies;

use App\Models\User;
use Modules\Banner\Models\Banner;

/**
 * spec/Banner_Management_Technical_Specification.md §6.3 — 1 permission duy nhất
 * (banner.manage), gán cho platform_ops + platform_content_head (§8 BannerPermissionSeeder).
 */
class BannerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('banner.manage');
    }

    public function view(User $user, Banner $banner): bool
    {
        return $user->can('banner.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('banner.manage');
    }

    public function update(User $user, Banner $banner): bool
    {
        return $user->can('banner.manage');
    }

    public function delete(User $user, Banner $banner): bool
    {
        return $user->can('banner.manage');
    }
}

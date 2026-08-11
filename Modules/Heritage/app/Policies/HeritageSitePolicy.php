<?php

namespace Modules\Heritage\Policies;

use App\Models\User;
use Modules\Heritage\Models\HeritageSite;

/** spec/Heritage_Technical_Specification.md §4 — 1 permission duy nhất (heritage.manage). */
class HeritageSitePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('heritage.manage');
    }

    public function view(User $user, HeritageSite $site): bool
    {
        return $user->can('heritage.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('heritage.manage');
    }

    public function update(User $user, HeritageSite $site): bool
    {
        return $user->can('heritage.manage');
    }

    public function delete(User $user, HeritageSite $site): bool
    {
        return $user->can('heritage.manage');
    }
}

<?php

namespace Modules\Ocop\Policies;

use App\Models\User;
use Modules\Ocop\Models\OcopProduct;

/** spec/Province_Showcase_Technical_Specification.md §6.1 — 1 permission duy nhất (ocop.manage). */
class OcopProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ocop.manage');
    }

    public function view(User $user, OcopProduct $product): bool
    {
        return $user->can('ocop.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('ocop.manage');
    }

    public function update(User $user, OcopProduct $product): bool
    {
        return $user->can('ocop.manage');
    }

    public function delete(User $user, OcopProduct $product): bool
    {
        return $user->can('ocop.manage');
    }
}

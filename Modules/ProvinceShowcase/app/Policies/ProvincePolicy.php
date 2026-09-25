<?php

namespace Modules\ProvinceShowcase\Policies;

use App\Models\Province;
use App\Models\User;

class ProvincePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('province.manage');
    }

    public function view(User $user, Province $province): bool
    {
        return $user->can('province.manage');
    }

    public function update(User $user, Province $province): bool
    {
        return $user->can('province.manage');
    }
}

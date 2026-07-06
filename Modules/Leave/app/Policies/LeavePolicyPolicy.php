<?php

namespace Modules\Leave\Policies;

use App\Models\User;
use Modules\Leave\Models\LeavePolicy;

class LeavePolicyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR', 'CEO']);
    }

    public function view(User $user, LeavePolicy $policy): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR', 'CEO']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR']);
    }

    public function update(User $user, LeavePolicy $policy): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR']);
    }

    public function delete(User $user, LeavePolicy $policy): bool
    {
        return $user->hasRole('System_Admin');
    }
}

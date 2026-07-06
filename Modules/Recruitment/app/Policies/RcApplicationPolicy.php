<?php

namespace Modules\Recruitment\Policies;

use App\Models\User;
use Modules\Recruitment\Models\RcApplication;

class RcApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['recruitment.view', 'recruitment.manage']);
    }

    public function view(User $user, RcApplication $application): bool
    {
        return $user->hasAnyPermission(['recruitment.view', 'recruitment.manage']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['recruitment.create', 'recruitment.manage']);
    }

    public function update(User $user, RcApplication $application): bool
    {
        return $user->hasAnyPermission(['recruitment.edit', 'recruitment.manage']);
    }
}

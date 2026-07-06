<?php

namespace Modules\Recruitment\Policies;

use App\Models\User;
use Modules\Recruitment\Models\RcCandidate;

class RcCandidatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['recruitment.view', 'recruitment.manage']);
    }

    public function view(User $user, RcCandidate $candidate): bool
    {
        return $user->hasAnyPermission(['recruitment.view', 'recruitment.manage']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['recruitment.create', 'recruitment.manage']);
    }

    public function update(User $user, RcCandidate $candidate): bool
    {
        return $user->hasAnyPermission(['recruitment.edit', 'recruitment.manage']);
    }

    public function delete(User $user, RcCandidate $candidate): bool
    {
        return $user->hasPermissionTo('recruitment.manage');
    }
}

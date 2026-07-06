<?php

namespace Modules\JobTitle\Policies;

use App\Models\User;
use Modules\JobTitle\Models\JobTitle;

class JobTitlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR', 'CEO', 'Viewer', 'Ops']);
    }

    public function view(User $user, JobTitle $jobTitle): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR', 'CEO', 'Viewer', 'Ops']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR']);
    }

    public function update(User $user, JobTitle $jobTitle): bool
    {
        if ($jobTitle->is_locked) {
            return false;
        }

        return $user->hasAnyRole(['System_Admin', 'HR']);
    }

    public function delete(User $user, JobTitle $jobTitle): bool
    {
        if ($jobTitle->is_locked) {
            return false;
        }

        return $user->hasRole('System_Admin');
    }
}

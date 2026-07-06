<?php

namespace Modules\Project\Policies;

use App\Models\User;
use Modules\Project\Models\Project;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'CEO', 'HR', 'Ops', 'Sales', 'Marketing', 'Viewer']);
    }

    public function view(User $user, Project $project): bool
    {
        return $user->hasAnyRole(['System_Admin', 'CEO', 'HR', 'Ops', 'Sales', 'Marketing', 'Viewer']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'CEO', 'HR', 'Ops']);
    }

    public function update(User $user, Project $project): bool
    {
        return $user->hasAnyRole(['System_Admin', 'CEO', 'HR', 'Ops']);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->hasAnyRole(['System_Admin', 'CEO']);
    }
}

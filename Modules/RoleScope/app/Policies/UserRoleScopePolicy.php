<?php

namespace Modules\RoleScope\Policies;

use App\Models\User;
use Modules\RoleScope\Models\UserRoleScope;

class UserRoleScopePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR', 'CEO']);
    }

    public function view(User $user, UserRoleScope $scope): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR', 'CEO']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR']);
    }

    public function update(User $user, UserRoleScope $scope): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR']);
    }

    public function delete(User $user, UserRoleScope $scope): bool
    {
        return $user->hasRole('System_Admin');
    }
}

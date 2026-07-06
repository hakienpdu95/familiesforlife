<?php

namespace Modules\Employee\Policies;

use App\Models\User;
use Modules\Employee\Models\Employee;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR', 'CEO', 'Viewer', 'Ops']);
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR', 'CEO', 'Viewer', 'Ops']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR']);
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR']);
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->hasRole('System_Admin');
    }

    public function transfer(User $user, Employee $employee): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR']);
    }

    public function offboard(User $user, Employee $employee): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR']);
    }

    public function viewSalary(User $user, Employee $employee): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR']);
    }

    public function updateSalary(User $user, Employee $employee): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR']);
    }
}

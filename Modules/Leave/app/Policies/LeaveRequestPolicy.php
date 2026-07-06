<?php

namespace Modules\Leave\Policies;

use App\Models\User;
use Modules\Leave\Models\LeaveRequest;

class LeaveRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR', 'CEO', 'Manager', 'Sales', 'Ops']);
    }

    public function view(User $user, LeaveRequest $request): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR', 'CEO', 'Manager'])
            || $user->id === $request->created_by;
    }

    public function create(User $user): bool
    {
        return true; // All authenticated users can create leave requests
    }

    public function approve(User $user, LeaveRequest $request): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR', 'Manager', 'CEO']);
    }

    public function reject(User $user, LeaveRequest $request): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR', 'Manager', 'CEO']);
    }

    public function cancel(User $user, LeaveRequest $request): bool
    {
        return $user->id === $request->created_by
            || $user->hasAnyRole(['System_Admin', 'HR']);
    }
}

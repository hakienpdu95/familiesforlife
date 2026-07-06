<?php

namespace Modules\KpiGoal\Policies;

use App\Models\User;
use Modules\KpiGoal\Models\KpiGoal;

class KpiGoalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR', 'CEO', 'Manager', 'Sales', 'Ops']);
    }

    public function view(User $user, KpiGoal $goal): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR', 'CEO', 'Manager'])
            || $user->id === $goal->created_by;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR', 'Manager', 'CEO']);
    }

    public function update(User $user, KpiGoal $goal): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR', 'Manager', 'CEO'])
            && $goal->isEditable();
    }

    public function approve(User $user, KpiGoal $goal): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR', 'Manager', 'CEO']);
    }

    public function updateProgress(User $user, KpiGoal $goal): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR', 'Manager', 'CEO'])
            || $user->id === $goal->created_by;
    }

    public function closeCycle(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR', 'CEO']);
    }

    public function viewLeaderboard(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'HR', 'CEO', 'Manager']);
    }

    public function delete(User $user, KpiGoal $goal): bool
    {
        return $user->hasRole('System_Admin');
    }
}

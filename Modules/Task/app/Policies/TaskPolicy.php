<?php

namespace Modules\Task\Policies;

use App\Models\User;
use Modules\Task\Models\Task;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'CEO', 'HR', 'Ops', 'Sales', 'Marketing', 'Viewer', 'AI_Operator']);
    }

    public function view(User $user, Task $task): bool
    {
        return $user->hasAnyRole(['System_Admin', 'CEO', 'HR', 'Ops', 'Sales', 'Marketing', 'Viewer', 'AI_Operator']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['System_Admin', 'CEO', 'HR', 'Ops']);
    }

    public function update(User $user, Task $task): bool
    {
        return $user->hasAnyRole(['System_Admin', 'CEO', 'HR', 'Ops']);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->hasAnyRole(['System_Admin', 'CEO', 'Ops']);
    }
}

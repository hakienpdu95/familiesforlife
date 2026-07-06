<?php
namespace App\Policies;

use App\Enums\PermissionEnum as P;
use App\Models\Tasks\Task;
use App\Models\User;

class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        if ($task->organization_id !== $user->current_organization_id) return false;

        if ($user->hasPermissionTo(P::TASKS_VIEW_ALL->value))      return true;  // CEO, Ops
        if ($user->hasPermissionTo(P::TASKS_VIEW_ASSIGNED->value)) return $task->assigned_to === $user->id;
        if ($user->hasPermissionTo(P::TASKS_VIEW_DEPT->value))     return $task->department === 'hr';
        if ($user->hasPermissionTo(P::TASKS_VIEW_LIMITED->value))  return $task->visibility === 'public';

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(P::TASKS_CREATE->value);
    }

    public function update(User $user, Task $task): bool
    {
        if ($task->organization_id !== $user->current_organization_id) return false;
        // Ops có tasks.edit, CEO có tasks.edit
        // Sales có tasks.create nhưng KHÔNG có tasks.edit → không update
        return $user->hasPermissionTo(P::TASKS_EDIT->value);
    }

    public function assign(User $user): bool
    {
        return $user->hasPermissionTo(P::TASKS_ASSIGN->value);
    }

    public function close(User $user, Task $task): bool
    {
        if ($task->organization_id !== $user->current_organization_id) return false;
        return $user->hasPermissionTo(P::TASKS_CLOSE->value);
    }
}
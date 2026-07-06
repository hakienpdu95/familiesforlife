<?php
namespace App\Policies;

use App\Enums\PermissionEnum as P;
use App\Models\Workflow\Workflow;
use App\Models\User;

class WorkflowPolicy
{
    public function view(User $user, Workflow $workflow): bool
    {
        if ($workflow->organization_id !== $user->current_organization_id) return false;

        if ($user->hasPermissionTo(P::WORKFLOW_MONITOR->value))      return true; // CEO, Ops
        if ($user->hasPermissionTo(P::WORKFLOW_AI_CONFIG->value))    return true; // AI_OP
        if ($user->hasPermissionTo(P::WORKFLOW_FULL_CONFIG->value))  return true; // Admin
        if ($user->hasPermissionTo(P::WORKFLOW_VIEW_LIMITED->value)) return $workflow->is_public;

        return false;
    }

    public function update(User $user, Workflow $workflow): bool
    {
        if ($workflow->organization_id !== $user->current_organization_id) return false;
        // CEO chỉ Monitor, không edit
        return $user->hasPermissionTo(P::WORKFLOW_EDIT->value)        // Ops
            || $user->hasPermissionTo(P::WORKFLOW_FULL_CONFIG->value); // Admin
    }

    public function configureAi(User $user): bool
    {
        return $user->hasAnyPermission([
            P::WORKFLOW_AI_CONFIG->value,
            P::WORKFLOW_FULL_CONFIG->value,
        ]);
    }
}
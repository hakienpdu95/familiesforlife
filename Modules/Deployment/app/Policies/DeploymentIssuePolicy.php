<?php

namespace Modules\Deployment\Policies;

use App\Models\User;
use Modules\Deployment\Models\DeploymentIssue;

class DeploymentIssuePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DeploymentIssue $issue): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['CEO', 'Ops', 'System_Admin'])
            || $user->roles->contains(fn ($r) => str_ends_with($r->name, '_pm'))
            || $user->roles->contains(fn ($r) => str_ends_with($r->name, '_surveyor'));
    }

    public function update(User $user, DeploymentIssue $issue): bool
    {
        $verticalCode = $issue->target?->vertical_code ?? '';
        return $user->hasAnyRole(['CEO', 'Ops', 'System_Admin'])
            || $user->hasRole($verticalCode . '_pm')
            || ($issue->owner_id === $user->id);
    }

    public function resolve(User $user, DeploymentIssue $issue): bool
    {
        return $this->update($user, $issue);
    }
}

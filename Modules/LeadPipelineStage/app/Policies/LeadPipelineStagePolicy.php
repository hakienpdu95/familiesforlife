<?php

namespace Modules\LeadPipelineStage\Policies;

use App\Enums\PermissionEnum as P;
use App\Models\User;
use Modules\LeadPipelineStage\Models\LeadPipelineStage;

class LeadPipelineStagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            P::LEADS_VIEW_ALL->value,
            P::LEADS_VIEW_ASSIGNED->value,
            P::LEADS_VIEW_SOURCE->value,
            P::LEADS_MANAGE_PIPELINE->value,
        ]);
    }

    public function create(User $user): bool
    {
        return $user->can(P::LEADS_MANAGE_PIPELINE->value);
    }

    public function update(User $user, LeadPipelineStage $stage): bool
    {
        return $user->can(P::LEADS_MANAGE_PIPELINE->value);
    }

    public function delete(User $user, LeadPipelineStage $stage): bool
    {
        return $user->can(P::LEADS_MANAGE_PIPELINE->value) && ! $stage->is_global;
    }
}

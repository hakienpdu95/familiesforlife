<?php

namespace Modules\LeadSource\Policies;

use App\Enums\PermissionEnum as P;
use App\Models\User;
use Modules\LeadSource\Models\LeadSource;

class LeadSourcePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            P::LEADS_VIEW_ALL->value,
            P::LEADS_VIEW_ASSIGNED->value,
            P::LEADS_VIEW_SOURCE->value,
            P::LEADS_MANAGE_SOURCES->value,
        ]);
    }

    public function create(User $user): bool
    {
        return $user->can(P::LEADS_MANAGE_SOURCES->value);
    }

    public function update(User $user, LeadSource $source): bool
    {
        return $user->can(P::LEADS_MANAGE_SOURCES->value);
    }

    public function delete(User $user, LeadSource $source): bool
    {
        return $user->can(P::LEADS_MANAGE_SOURCES->value) && ! $source->is_global;
    }
}

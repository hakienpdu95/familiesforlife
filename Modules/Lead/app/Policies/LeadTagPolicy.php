<?php

namespace Modules\Lead\Policies;

use App\Enums\PermissionEnum as P;
use App\Models\User;
use Modules\Lead\Models\LeadTagDefinition;

class LeadTagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(P::LEADS_VIEW_ALL->value)
            || $user->can(P::LEADS_VIEW_ASSIGNED->value)
            || $user->can(P::LEADS_VIEW_SOURCE->value)
            || $user->can(P::LEADS_MANAGE_TAGS->value);
    }

    public function create(User $user): bool
    {
        return $user->can(P::LEADS_MANAGE_TAGS->value);
    }

    public function update(User $user, LeadTagDefinition $tag): bool
    {
        return $user->can(P::LEADS_MANAGE_TAGS->value);
    }

    public function delete(User $user, LeadTagDefinition $tag): bool
    {
        return $user->can(P::LEADS_MANAGE_TAGS->value);
    }
}

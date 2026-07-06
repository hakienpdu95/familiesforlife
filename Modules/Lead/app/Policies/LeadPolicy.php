<?php

namespace Modules\Lead\Policies;

use App\Enums\PermissionEnum as P;
use App\Models\User;
use Modules\Lead\Models\Lead;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(P::LEADS_VIEW_ALL->value)
            || $user->can(P::LEADS_VIEW_ASSIGNED->value)
            || $user->can(P::LEADS_VIEW_SOURCE->value);
    }

    public function view(User $user, Lead $lead): bool
    {
        if ($user->can(P::LEADS_VIEW_ALL->value) || $user->can(P::LEADS_VIEW_SOURCE->value)) {
            return true;
        }

        if ($user->can(P::LEADS_VIEW_ASSIGNED->value)) {
            return (int) $lead->assigned_to === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can(P::LEADS_CREATE->value);
    }

    public function update(User $user, Lead $lead): bool
    {
        if (! $user->can(P::LEADS_EDIT->value)) {
            return false;
        }

        // Sales (view_assigned but not view_all) can only edit their own leads
        if (! $user->can(P::LEADS_VIEW_ALL->value) && $user->can(P::LEADS_VIEW_ASSIGNED->value)) {
            return (int) $lead->assigned_to === $user->id;
        }

        return true;
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->can(P::LEADS_DELETE->value);
    }

    public function assign(User $user, Lead $lead): bool
    {
        return $user->can(P::LEADS_ASSIGN->value);
    }

    public function export(User $user): bool
    {
        return $user->can(P::LEADS_EXPORT->value);
    }

    public function managePipeline(User $user): bool
    {
        return $user->can(P::LEADS_MANAGE_PIPELINE->value);
    }

    public function manageSources(User $user): bool
    {
        return $user->can(P::LEADS_MANAGE_SOURCES->value);
    }

    public function manageTags(User $user): bool
    {
        return $user->can(P::LEADS_MANAGE_TAGS->value);
    }

    /**
     * Returns the user ID to scope queries — null means unrestricted within org.
     */
    public function scopeUserId(User $user): ?int
    {
        if ($user->can(P::LEADS_VIEW_ALL->value)) {
            return null;
        }

        if ($user->can(P::LEADS_VIEW_ASSIGNED->value)) {
            return $user->id;
        }

        return null;
    }

    /**
     * Marketing (view_source) sees all leads but contact info must be masked.
     */
    public function shouldMaskContact(User $user): bool
    {
        return ! $user->can(P::LEADS_VIEW_ALL->value)
            && ! $user->can(P::LEADS_VIEW_ASSIGNED->value)
            && $user->can(P::LEADS_VIEW_SOURCE->value);
    }
}

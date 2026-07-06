<?php
namespace App\Policies;

use App\Enums\PermissionEnum as P;
use App\Models\Crm\Lead;
use App\Models\User;

class LeadPolicy
{
    private function sameTenant(User $u, Lead $l): bool
    {
        return $l->organization_id === $u->current_organization_id;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            P::LEADS_VIEW_ALL->value,
            P::LEADS_VIEW_ASSIGNED->value,
            P::LEADS_VIEW_SOURCE->value,
        ]);
    }

    public function view(User $user, Lead $lead): bool
    {
        if (!$this->sameTenant($user, $lead)) return false;

        // CEO — full access
        if ($user->hasPermissionTo(P::LEADS_VIEW_ALL->value)
            && $user->hasPermissionTo(P::LEADS_DELETE->value)) return true;

        // Ops, AI_OP — Limited: xem tất cả nhưng không edit
        if ($user->hasPermissionTo(P::LEADS_VIEW_ALL->value)) return true;

        // Sales — Assigned: chỉ lead của mình
        if ($user->hasPermissionTo(P::LEADS_VIEW_ASSIGNED->value))
            return $lead->assigned_to === $user->id;

        // Marketing — Source view: chỉ lead có lead_source
        if ($user->hasPermissionTo(P::LEADS_VIEW_SOURCE->value))
            return !is_null($lead->lead_source);

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(P::LEADS_CREATE->value);
    }

    public function update(User $user, Lead $lead): bool
    {
        if (!$this->sameTenant($user, $lead)) return false;
        if (!$user->hasPermissionTo(P::LEADS_EDIT->value)) return false;

        // Ops có view_all NHƯNG không có leads.edit → Limited đúng nghĩa
        // Sales có leads.edit → chỉ edit lead của mình
        if ($user->hasPermissionTo(P::LEADS_VIEW_ASSIGNED->value))
            return $lead->assigned_to === $user->id;

        // CEO có leads.delete (marker cho full) → edit tất cả
        return $user->hasPermissionTo(P::LEADS_DELETE->value);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $this->sameTenant($user, $lead)
            && $user->hasPermissionTo(P::LEADS_DELETE->value);
    }

    public function assign(User $user): bool
    {
        return $user->hasPermissionTo(P::LEADS_ASSIGN->value);
    }

    // Dùng trong Blade để biết có được xem contact details không
    public function viewContactDetails(User $user, Lead $lead): bool
    {
        if (!$this->sameTenant($user, $lead)) return false;
        // Marketing chỉ có view_source → KHÔNG được xem phone/email
        if ($user->hasPermissionTo(P::LEADS_VIEW_SOURCE->value)
            && !$user->hasPermissionTo(P::LEADS_VIEW_ALL->value)
            && !$user->hasPermissionTo(P::LEADS_VIEW_ASSIGNED->value))
            return false;
        return true;
    }
}
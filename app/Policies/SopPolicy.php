<?php
namespace App\Policies;

use App\Enums\PermissionEnum as P;
use App\Models\Tasks\Sop;
use App\Models\User;

class SopPolicy
{
    public function view(User $user, Sop $sop): bool
    {
        if ($sop->organization_id !== $user->current_organization_id) return false;

        if ($user->hasPermissionTo(P::SOP_VIEW->value))    return true;   // CEO, Ops, Viewer
        if ($user->hasPermissionTo(P::SOP_CREATE->value))  return true;   // Ops
        if ($user->hasPermissionTo(P::SOP_APPROVE->value)) return true;   // CEO

        // Sales + Marketing: View related
        if ($user->hasPermissionTo(P::SOP_VIEW_RELATED->value))
            return in_array($user->department, $sop->target_departments ?? []);

        // HR: chỉ SOP dept hr
        if ($user->hasPermissionTo(P::SOP_CREATE_HR->value))
            return $sop->department === 'hr';

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission([P::SOP_CREATE->value, P::SOP_CREATE_HR->value]);
    }

    public function update(User $user, Sop $sop): bool
    {
        if ($sop->organization_id !== $user->current_organization_id) return false;
        if ($user->hasPermissionTo(P::SOP_EDIT->value)) return true; // Ops
        if ($user->hasPermissionTo(P::SOP_CREATE_HR->value)) return $sop->department === 'hr'; // HR
        return false;
    }

    public function approve(User $user, Sop $sop): bool
    {
        return $user->hasPermissionTo(P::SOP_APPROVE->value)
            && $sop->organization_id === $user->current_organization_id;
    }

    public function configureAi(User $user): bool
    {
        return $user->hasPermissionTo(P::SOP_AI_CONFIG->value);
    }
}
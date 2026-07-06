<?php

namespace Modules\Sop\Policies;

use App\Models\User;
use Modules\Sop\Models\SopProcess;

class SopPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'sop.view', 'sop.view_related', 'sop.create', 'sop.edit',
            'sop.create_hr', 'sop.approve', 'sop.config',
        ]);
    }

    public function view(User $user, SopProcess $sop): bool
    {
        return $user->hasAnyPermission(['sop.view', 'sop.view_related', 'sop.create', 'sop.edit', 'sop.approve', 'sop.config']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['sop.create', 'sop.create_hr', 'sop.config']);
    }

    public function update(User $user, SopProcess $sop): bool
    {
        if ($sop->status?->value === 'approved') {
            return false;
        }

        return $user->hasAnyPermission(['sop.edit', 'sop.create_hr', 'sop.config']);
    }

    public function delete(User $user, SopProcess $sop): bool
    {
        return $user->hasAnyPermission(['sop.edit', 'sop.config']);
    }

    public function approve(User $user, SopProcess $sop): bool
    {
        return $user->hasAnyPermission(['sop.approve', 'sop.config']);
    }

    public function submitReview(User $user, SopProcess $sop): bool
    {
        return $user->hasAnyPermission(['sop.create', 'sop.edit', 'sop.create_hr', 'sop.config']);
    }

    public function manageRaci(User $user, SopProcess $sop): bool
    {
        return $user->hasAnyPermission(['sop.edit', 'sop.create_hr', 'sop.config']);
    }
}

<?php

namespace Modules\User\Policies;

use App\Enums\RoleEnum;
use App\Models\User;

class UserPolicy
{
    /** Super-admin bypass handled by Gate::before() in AppServiceProvider. */

    public function viewAny(User $actor): bool
    {
        return $actor->hasAnyRole([
            'super-admin',
            RoleEnum::ADMIN->value,
            RoleEnum::CEO->value,
            RoleEnum::HR->value,
        ]);
    }

    public function view(User $actor, User $target): bool
    {
        if ($actor->hasAnyRole(['super-admin', RoleEnum::ADMIN->value])) {
            return true;
        }

        if ($actor->hasAnyRole([RoleEnum::CEO->value, RoleEnum::HR->value])) {
            return $actor->organization_id === $target->organization_id;
        }

        return false;
    }

    public function create(User $actor): bool
    {
        return $actor->hasAnyRole(['super-admin', RoleEnum::ADMIN->value, RoleEnum::HR->value]);
    }

    public function update(User $actor, User $target): bool
    {
        // Prevent self-editing through admin panel — use profile settings instead.
        if ($actor->id === $target->id) {
            return false;
        }

        if ($actor->hasAnyRole(['super-admin', RoleEnum::ADMIN->value])) {
            return true;
        }

        if ($actor->hasRole(RoleEnum::HR->value)) {
            return $actor->organization_id === $target->organization_id;
        }

        return false;
    }

    public function delete(User $actor, User $target): bool
    {
        // Cannot delete yourself
        if ($actor->id === $target->id) {
            return false;
        }

        return $actor->hasAnyRole(['super-admin', RoleEnum::ADMIN->value]);
    }
}

<?php

namespace Modules\User\Policies;

use App\Enums\RoleEnum;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $this->isSuperAdmin($actor);
    }

    public function create(User $actor): bool
    {
        return $this->isSuperAdmin($actor);
    }

    public function update(User $actor, User $target): bool
    {
        return $this->isSuperAdmin($actor)
            && $target->isPlatform()
            && ! $this->isSuperAdmin($target);
    }

    public function deactivate(User $actor, User $target): bool
    {
        return $this->update($actor, $target)
            && $target->is_active
            && $target->id !== $actor->id;
    }

    public function activate(User $actor, User $target): bool
    {
        return $this->update($actor, $target) && ! $target->is_active;
    }

    public function sendPasswordResetLink(User $actor, User $target): bool
    {
        return $this->update($actor, $target) && $target->is_active;
    }

    private function isSuperAdmin(User $user): bool
    {
        return $user->hasGlobalRole(RoleEnum::SUPER_ADMIN->value);
    }
}

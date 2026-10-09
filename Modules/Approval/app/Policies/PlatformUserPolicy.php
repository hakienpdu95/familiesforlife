<?php

namespace Modules\Approval\Policies;

use App\Models\User;
use Closure;

class PlatformUserPolicy
{
    public function manage(User $actor): bool
    {
        return $this->atPlatformTeam(
            fn () => $actor->organization_id === null && $actor->hasRole('super-admin')
        );
    }

    public function update(User $actor, User $target): bool
    {
        return $this->manage($actor) && $this->atPlatformTeam(
            fn () => $target->organization_id === null && ! $target->hasRole('super-admin')
        );
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

    public function resetPassword(User $actor, User $target): bool
    {
        return $this->update($actor, $target);
    }

    private function atPlatformTeam(Closure $check): bool
    {
        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId(null);

        try {
            return $check();
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }
}

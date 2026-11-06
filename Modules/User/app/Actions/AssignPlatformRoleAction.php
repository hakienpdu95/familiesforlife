<?php

namespace Modules\User\Actions;

use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class AssignPlatformRoleAction
{
    use AsAction;

    public function handle(User $user, string $role): void
    {
        $user->syncRoles([$role]);
    }
}

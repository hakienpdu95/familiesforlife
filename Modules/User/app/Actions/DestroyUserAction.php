<?php

namespace Modules\User\Actions;

use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Shared\Tenancy\Models\OrganizationMember;

class DestroyUserAction
{
    use AsAction;

    public function handle(User $user): string
    {
        $name = $user->name;
        OrganizationMember::where('user_id', $user->id)->delete();
        $user->delete();

        return $name;
    }
}

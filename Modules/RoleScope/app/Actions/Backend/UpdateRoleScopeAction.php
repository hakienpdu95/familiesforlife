<?php

namespace Modules\RoleScope\Actions\Backend;

use Carbon\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\RoleScope\Data\Requests\UpdateRoleScopeData;
use Modules\RoleScope\Models\UserRoleScope;

class UpdateRoleScopeAction
{
    use AsAction;

    public function handle(UserRoleScope $scope, UpdateRoleScopeData $data): UserRoleScope
    {
        $scope->update([
            'expires_at' => $data->expires_at ? Carbon::parse($data->expires_at) : null,
            'note'       => $data->note,
        ]);

        return $scope;
    }
}

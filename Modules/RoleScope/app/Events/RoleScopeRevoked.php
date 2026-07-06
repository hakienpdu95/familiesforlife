<?php

namespace Modules\RoleScope\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\RoleScope\Models\UserRoleScope;

class RoleScopeRevoked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly UserRoleScope $roleScope,
        public readonly string        $userName,
        public readonly string        $roleName,
    ) {}
}

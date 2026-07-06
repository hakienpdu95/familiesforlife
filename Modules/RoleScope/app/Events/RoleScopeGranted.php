<?php

namespace Modules\RoleScope\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\RoleScope\Models\UserRoleScope;

class RoleScopeGranted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly UserRoleScope $roleScope,
    ) {}
}

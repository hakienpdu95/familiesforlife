<?php

namespace Modules\User\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRoleAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User   $user,
        public readonly string $role,
    ) {}
}

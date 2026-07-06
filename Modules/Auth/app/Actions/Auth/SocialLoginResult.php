<?php

namespace Modules\Auth\Actions\Auth;

use App\Models\User;

final readonly class SocialLoginResult
{
    public function __construct(
        public User $user,
        public bool $isNewUser,
        public bool $isNewLink,
    ) {}
}

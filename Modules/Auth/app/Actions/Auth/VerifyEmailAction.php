<?php

namespace Modules\Auth\Actions\Auth;

use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Lorisleiva\Actions\Concerns\AsAction;

class VerifyEmailAction
{
    use AsAction;

    public function handle(MustVerifyEmail $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return false;
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return true;
    }
}

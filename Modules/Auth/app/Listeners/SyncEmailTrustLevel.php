<?php

namespace Modules\Auth\Listeners;

use Illuminate\Auth\Events\Verified;

class SyncEmailTrustLevel
{
    public function handle(Verified $event): void
    {
        $user = $event->user;

        if ($user->trust_level < 1) {
            $user->update(['trust_level' => 1]);
        }
    }
}

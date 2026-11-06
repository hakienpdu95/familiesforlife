<?php

namespace Modules\User\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\User\Notifications\AccountInvitationNotification;

class SendPasswordSetupLinkAction
{
    use AsAction;

    public function handle(User $user, bool $invitation = false): void
    {
        $token = Password::broker(config('fortify.passwords'))->createToken($user);

        if ($invitation) {
            $user->notify(new AccountInvitationNotification($token));
        } else {
            $user->sendPasswordResetNotification($token);
        }

        ActivityLogger::info('User', $invitation ? 'user_invitation_sent' : 'user_password_reset_link_sent', $user);
    }
}

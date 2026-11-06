<?php

namespace Modules\User\Actions;

use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ActivityLog\Core\ActivityLogger;

class ActivateAccountAction
{
    use AsAction;

    public function handle(User $user): void
    {
        $user->update(['is_active' => true]);

        ActivityLogger::info('User', 'user_activated', $user);
    }
}

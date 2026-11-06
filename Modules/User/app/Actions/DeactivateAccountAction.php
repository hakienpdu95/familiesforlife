<?php

namespace Modules\User\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ActivityLog\Core\ActivityLogger;

class DeactivateAccountAction
{
    use AsAction;

    public function handle(User $user, string $via = 'admin_ui'): void
    {
        $user->forceFill(['is_active' => false, 'remember_token' => null])->save();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        ActivityLogger::info('User', 'user_deactivated', $user, ['via' => $via]);
    }
}

<?php

namespace Modules\User\Actions;

use App\Enums\AccountType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\User\Data\CreateAccountData;
use Modules\User\Events\UserCreated;
use Modules\User\Events\UserRoleAssigned;

class CreateAccountAction
{
    use AsAction;

    public function handle(CreateAccountData $data, string $via = 'admin_ui'): User
    {
        $user = DB::transaction(function () use ($data): User {
            $user = new User;
            $user->forceFill([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make(Str::password(40)),
                'account_type' => AccountType::Platform,
                'email_verified_at' => now(),
                'is_active' => true,
            ])->save();

            AssignPlatformRoleAction::run($user, $data->role);

            return $user;
        });

        event(new UserCreated($user, $via));
        event(new UserRoleAssigned($user, $data->role));

        SendPasswordSetupLinkAction::run($user, invitation: true);

        return $user;
    }
}

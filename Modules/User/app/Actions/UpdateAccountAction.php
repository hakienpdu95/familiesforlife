<?php

namespace Modules\User\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\User\Data\UpdateAccountData;
use Modules\User\Events\UserRoleAssigned;

class UpdateAccountAction
{
    use AsAction;

    public function handle(User $user, UpdateAccountData $data): User
    {
        $previousRole = $user->getRoleNames()->first();

        DB::transaction(function () use ($user, $data): void {
            $user->update([
                'name' => $data->name,
                'username' => $data->username,
                'phone' => $data->phone,
                'job_title' => $data->job_title,
                'department' => $data->department,
            ]);
            AssignPlatformRoleAction::run($user, $data->role);
        });

        if ($previousRole !== $data->role) {
            event(new UserRoleAssigned($user, $data->role));
        }

        return $user;
    }
}

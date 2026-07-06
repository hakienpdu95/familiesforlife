<?php

namespace Modules\Auth\Actions\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateProfileAction implements UpdatesUserProfileInformation
{
    use AsAction;

    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ])->validateWithBag('updateProfileInformation');

        if ($input['email'] !== $user->email && $user instanceof MustVerifyEmail) {
            $user->forceFill([
                'name'              => $input['name'],
                'email'             => $input['email'],
                'email_verified_at' => null,
            ])->save();

            $user->sendEmailVerificationNotification();
        } else {
            $user->forceFill([
                'name'  => $input['name'],
                'email' => $input['email'],
            ])->save();
        }
    }
}

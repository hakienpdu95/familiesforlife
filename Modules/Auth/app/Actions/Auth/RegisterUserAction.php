<?php

namespace Modules\Auth\Actions\Auth;

use App\Enums\AccountType;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ActivityLog\Core\ActivityLogger;

class RegisterUserAction implements CreatesNewUsers
{
    use AsAction;

    public function create(array $input): User
    {
        $data = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Họ và tên là bắt buộc.',
            'email.unique' => 'Email này đã được sử dụng.',
        ])->validate();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'account_type' => AccountType::Free,
        ]);

        ActivityLogger::info('Auth', 'user_registered', $user, ['email' => $user->email]);

        return $user;
    }
}

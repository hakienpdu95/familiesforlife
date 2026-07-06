<?php

namespace Modules\Auth\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\Actions\RegisterOrganizationAction;
use Modules\Auth\Data\RegisterOrganizationData;

class RegisterUserAction implements CreatesNewUsers
{
    use AsAction;

    public function create(array $input): User
    {
        Validator::make($input, [
            'organization_name' => ['required', 'string', 'max:255'],
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password'          => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'organization_name.required' => 'Tên tổ chức là bắt buộc.',
            'name.required'              => 'Họ và tên là bắt buộc.',
            'email.unique'               => 'Email này đã được sử dụng.',
        ])->validate();

        return RegisterOrganizationAction::run(
            RegisterOrganizationData::from($input)
        );
    }
}

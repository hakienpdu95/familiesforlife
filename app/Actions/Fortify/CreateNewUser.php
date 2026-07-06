<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Modules\Auth\Actions\RegisterOrganizationAction;
use Modules\Auth\Data\RegisterOrganizationData;

/**
 * Fortify entry point: validate input → delegate sang RegisterOrganizationAction.
 * Logic nghiệp vụ nằm trong module Auth, không viết ở đây.
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input): User
    {
        Validator::make($input, [
            'organization_name' => ['required', 'string', 'max:255'],
            'name'              => ['required', 'string', 'max:255'],
            'email'             => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password'          => $this->passwordRules(),
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

<?php

namespace Modules\User\Data;

use App\Enums\RoleEnum;
use App\Support\Username;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class CreateAccountData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $username,
        public readonly string $role,
        public readonly ?string $phone = null,
        public readonly ?string $job_title = null,
        public readonly ?string $department = null,
    ) {}

    public static function prepareForPipeline(array $properties): array
    {
        return [
            ...AccountProfileFields::prepare($properties),
            'email' => Str::lower(trim((string) ($properties['email'] ?? ''))),
        ];
    }

    public static function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc,dns', 'max:255', Rule::unique('users', 'email')],
            'username' => ['nullable', ...array_slice(Username::rules(), 1)],
            'role' => [
                'required', 'string',
                Rule::in(RoleEnum::assignableValues()),
                Rule::exists('roles', 'name')->where('guard_name', 'web'),
            ],
            ...AccountProfileFields::rules(),
        ];
    }

    public static function messages(): array
    {
        return [
            'email.unique' => 'Email này đã được sử dụng.',
            ...AccountProfileFields::messages(),
        ];
    }
}

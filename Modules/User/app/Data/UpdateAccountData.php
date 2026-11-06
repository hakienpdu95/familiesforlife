<?php

namespace Modules\User\Data;

use App\Enums\RoleEnum;
use App\Support\Username;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class UpdateAccountData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $username,
        public readonly string $role,
        public readonly ?string $phone = null,
        public readonly ?string $job_title = null,
        public readonly ?string $department = null,
    ) {}

    public static function prepareForPipeline(array $properties): array
    {
        return AccountProfileFields::prepare($properties);
    }

    public static function rules(ValidationContext $context): array
    {
        return [
            'username' => Username::rules($context->fullPayload['id'] ?? null),
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
        return AccountProfileFields::messages();
    }
}

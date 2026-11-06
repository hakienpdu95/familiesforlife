<?php

namespace Modules\User\Data;

use App\Enums\RoleEnum;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateAccountData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $name,

        public readonly string $role,
    ) {}

    public static function rules(): array
    {
        return [
            'role' => [
                'required', 'string',
                Rule::in(RoleEnum::assignableValues()),
                Rule::exists('roles', 'name')->where('guard_name', 'web'),
            ],
        ];
    }

    public static function messages(): array
    {
        return [
            'role.in' => 'Vai trò không hợp lệ.',
            'role.exists' => 'Vai trò chưa tồn tại trong bảng roles — chạy seeder tương ứng trước.',
        ];
    }
}

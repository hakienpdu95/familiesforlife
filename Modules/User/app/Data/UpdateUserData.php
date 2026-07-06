<?php

namespace Modules\User\Data;

use App\Enums\RoleEnum;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateUserData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $name,

        public readonly string $email,

        // nullable — empty string means no change
        public readonly ?string $password,

        #[Required, Exists('organizations', 'id')]
        public readonly int $organization_id,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $department,

        public readonly string $system_role,

        public readonly bool $is_active = false,
    ) {}

    public static function rules(): array
    {
        $currentId    = request()->route('user')?->id;
        $allowedRoles = implode(',', array_column(RoleEnum::cases(), 'value'));

        return [
            'email'       => [
                'required', 'email:rfc', 'max:255',
                Rule::unique('users', 'email')->ignore($currentId),
            ],
            'password'    => [
                'nullable', 'string', 'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers(),
            ],
            'system_role' => ['required', 'string', "in:$allowedRoles"],
        ];
    }

    public static function messages(): array
    {
        return [
            'email.unique'         => 'Email này đã được sử dụng bởi tài khoản khác.',
            'password.mixed_case'  => 'Mật khẩu mới phải có cả chữ HOA và chữ thường.',
            'password.letters'     => 'Mật khẩu mới phải chứa ít nhất một chữ cái.',
            'password.numbers'     => 'Mật khẩu mới phải chứa ít nhất một chữ số.',
            'password.confirmed'   => 'Xác nhận mật khẩu mới không khớp.',
            'system_role.in'       => 'Vai trò không hợp lệ.',
        ];
    }
}

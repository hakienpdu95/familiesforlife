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

class StoreUserData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $name,

        // email + password validated via rules() — needs dynamic/complex rules
        public readonly string $email,
        public readonly string $password,

        #[Required, Exists('organizations', 'id')]
        public readonly int $organization_id,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $department,

        // system_role validated via rules()
        public readonly string $system_role,

        public readonly bool $is_active = true,

        public readonly bool $send_welcome_email = false,
    ) {}

    public static function rules(): array
    {
        $allowedRoles = implode(',', array_column(RoleEnum::cases(), 'value'));

        return [
            'email'       => ['required', 'email:rfc,dns', 'max:255', Rule::unique('users', 'email')],
            'password'    => [
                'required', 'string', 'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers(),
            ],
            'system_role' => ['required', 'string', "in:$allowedRoles"],
        ];
    }

    public static function messages(): array
    {
        return [
            'email.unique'            => 'Email này đã được sử dụng. Nếu user đang ở tổ chức khác, hãy dùng chức năng mời.',
            'password.min'            => 'Mật khẩu tối thiểu 8 ký tự.',
            'password.mixed_case'     => 'Mật khẩu phải có cả chữ HOA và chữ thường.',
            'password.letters'        => 'Mật khẩu phải chứa ít nhất một chữ cái.',
            'password.numbers'        => 'Mật khẩu phải chứa ít nhất một chữ số.',
            'password.confirmed'      => 'Xác nhận mật khẩu không khớp.',
            'system_role.in'          => 'Vai trò không hợp lệ.',
            'organization_id.exists'  => 'Tổ chức không tồn tại.',
        ];
    }
}

<?php

namespace Modules\Approval\Data;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/**
 * spec/Platform_RBAC_Phase2_Specification.md §2.4 — tạo user Platform
 * (organization_id=null) qua UI, thay cho `platform:user-create` CLI. CỐ Ý không nhận
 * 'super-admin' làm role hợp lệ — giữ nguyên quyết định đã chốt ở
 * spec/Platform_RBAC_Technical_Specification.md §3.8.
 */
class StorePlatformUserData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $name,

        public readonly string $email,
        public readonly string $password,
        public readonly string $role,
    ) {}

    public static function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc,dns', 'max:255', Rule::unique('users', 'email')],
            'password' => [
                'required', 'string', 'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers(),
            ],
            'role' => ['required', 'string', Rule::in(User::assignablePlatformRoles())],
        ];
    }

    public static function messages(): array
    {
        return [
            'email.unique'   => 'Email này đã được sử dụng.',
            'password.min'   => 'Mật khẩu tối thiểu 8 ký tự.',
            'role.in'        => 'Vai trò không hợp lệ.',
        ];
    }
}

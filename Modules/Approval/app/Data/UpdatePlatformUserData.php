<?php

namespace Modules\Approval\Data;

use App\Models\User;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/**
 * spec/Platform_RBAC_Phase2_Specification.md §2.4 — chỉ cho đổi name/role, KHÔNG cho đổi
 * email (giữ nguyên convention UpdateUserData không cho đổi định danh) — đổi mật khẩu là
 * luồng riêng, không gộp vào form sửa role.
 */
class UpdatePlatformUserData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $name,

        public readonly string $role,
    ) {}

    public static function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::in(User::assignablePlatformRoles())],
        ];
    }

    public static function messages(): array
    {
        return [
            'role.in' => 'Vai trò không hợp lệ.',
        ];
    }
}

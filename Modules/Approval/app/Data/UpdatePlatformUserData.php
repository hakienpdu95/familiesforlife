<?php

namespace Modules\Approval\Data;

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
    private const ALLOWED_ROLES = [
        'platform_content_head',
        'platform_content_editor',
        'platform_content_moderator',
        'platform_ops',
        'platform_viewer',
    ];

    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $name,

        public readonly string $role,
    ) {}

    public static function rules(): array
    {
        return [
            'role' => ['required', 'string', 'in:' . implode(',', self::ALLOWED_ROLES)],
        ];
    }

    public static function messages(): array
    {
        return [
            'role.in' => 'Vai trò không hợp lệ.',
        ];
    }
}

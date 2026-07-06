<?php

namespace Modules\RoleScope\Data\Requests;

use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class GrantRoleScopeData extends Data
{
    public function __construct(
        #[Required, IntegerType]
        public readonly int $organization_id,

        #[Required, IntegerType]
        public readonly int $user_id,

        #[Required, IntegerType]
        public readonly int $role_id,

        #[Nullable, IntegerType]
        public readonly ?int $scope_branch_id,

        #[Nullable, IntegerType]
        public readonly ?int $scope_dept_id,

        #[Nullable]
        public readonly ?string $expires_at,

        #[Nullable, Max(500)]
        public readonly ?string $note,
    ) {}

    public static function rules(): array
    {
        $orgId = (int) request('organization_id');

        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'role_id'         => ['required', 'integer', Rule::exists('roles', 'id')],
            'scope_branch_id' => [
                'nullable', 'integer',
                Rule::exists('branches', 'id')->where('organization_id', $orgId),
            ],
            'scope_dept_id' => [
                'nullable', 'integer',
                Rule::exists('departments', 'id')->where('organization_id', $orgId),
            ],
            'expires_at' => ['nullable', 'date', 'after:now'],
            // Combined user_id rules (original had duplicate key — only last survives in PHP)
            'user_id' => [
                'required', 'integer',
                Rule::exists('users', 'id')->where('organization_id', $orgId),
                Rule::unique('user_role_scopes')
                    ->where('role_id', request()->input('role_id'))
                    ->where('scope_branch_id', request()->input('scope_branch_id'))
                    ->where('scope_dept_id', request()->input('scope_dept_id'))
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public static function messages(): array
    {
        return [
            'organization_id.required' => 'Vui lòng chọn tổ chức.',
            'organization_id.exists'   => 'Tổ chức không hợp lệ.',
            'user_id.required'         => 'Vui lòng chọn user.',
            'user_id.exists'           => 'User không thuộc tổ chức đã chọn.',
            'user_id.unique'           => 'Tổ hợp user + role + phạm vi này đã tồn tại.',
            'role_id.required'         => 'Vui lòng chọn role.',
            'expires_at.after'         => 'Ngày hết hạn phải sau thời điểm hiện tại.',
        ];
    }
}

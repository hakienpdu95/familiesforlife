<?php

namespace Modules\Department\Data\Requests;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Validation\Rule;
use Modules\Department\Enums\DepartmentFunction;
use Modules\Department\Enums\DepartmentStatus;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateDepartmentData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $name,

        #[Required, StringType, Max(50)]
        public readonly string $code,

        public readonly DepartmentStatus $status,

        #[Nullable]
        public readonly ?DepartmentFunction $function,

        #[Nullable]
        public readonly ?int $parent_id,

        #[Nullable]
        public readonly ?int $branch_id,

        #[Nullable]
        public readonly ?int $merged_into_id,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $budget_code,

        #[Nullable]
        public readonly ?int $headcount_limit,

        #[Nullable, StringType]
        public readonly ?string $description,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $internal_phone,

        #[Nullable, Email, Max(255)]
        public readonly ?string $internal_email,

        #[Nullable]
        public readonly ?string $effective_from,

        #[Nullable]
        public readonly ?string $effective_to,
    ) {}

    public static function rules(): array
    {
        $orgId     = TenantContext::getOrganizationId();
        $currentId = request()->route('department')?->id;

        return [
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('departments', 'code')
                    ->where('organization_id', $orgId)
                    ->whereNull('deleted_at')
                    ->ignore($currentId),
            ],
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('departments', 'id')
                    ->where('organization_id', $orgId)
                    ->whereNull('deleted_at'),
            ],
            'branch_id' => [
                'nullable', 'integer',
                Rule::exists('branches', 'id')
                    ->where('organization_id', $orgId)
                    ->whereNull('deleted_at'),
            ],
            'merged_into_id' => [
                'nullable', 'integer',
                Rule::exists('departments', 'id')
                    ->where('organization_id', $orgId)
                    ->whereNull('deleted_at'),
            ],
            'headcount_limit' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'effective_from'  => ['nullable', 'date'],
            'effective_to'    => ['nullable', 'date'],
        ];
    }
}

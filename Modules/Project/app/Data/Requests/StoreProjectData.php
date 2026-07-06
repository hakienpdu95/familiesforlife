<?php

namespace Modules\Project\Data\Requests;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Validation\Rule;
use Modules\Project\Enums\ProjectPriority;
use Modules\Project\Enums\ProjectStatus;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreProjectData extends Data
{
    public function __construct(
        public readonly int $organization_id,

        #[Required, StringType, Max(255)]
        public readonly string $name,

        #[Required, StringType, Max(50)]
        public readonly string $code,

        public readonly ProjectStatus $status,

        public readonly ProjectPriority $priority,

        #[Required]
        public readonly int $owner_id,

        #[Nullable]
        public readonly ?int $branch_id,

        #[Nullable]
        public readonly ?int $department_id,

        #[Nullable, StringType, Max(50)]
        public readonly ?string $category,

        #[Nullable, StringType]
        public readonly ?string $description,

        #[Nullable]
        public readonly ?string $start_date,

        #[Nullable]
        public readonly ?string $end_date,

        #[Nullable]
        public readonly ?string $budget,

        #[Nullable, StringType, Max(3)]
        public readonly ?string $currency,
    ) {}

    public static function rules(): array
    {
        $orgId = TenantContext::getOrganizationId();

        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('projects', 'code')
                    ->where('organization_id', $orgId)
                    ->whereNull('deleted_at'),
            ],
            'owner_id'      => ['required', 'integer', Rule::exists('employees', 'id')->where('organization_id', $orgId)],
            'branch_id'     => ['nullable', 'integer', Rule::exists('branches', 'id')->where('organization_id', $orgId)],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where('organization_id', $orgId)],
            'start_date'    => ['nullable', 'date'],
            'end_date'      => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget'        => ['nullable', 'numeric', 'min:0'],
            'currency'      => ['nullable', 'string', 'size:3'],
        ];
    }
}

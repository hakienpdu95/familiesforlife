<?php

namespace Modules\Employee\Data\Requests;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class TransferEmployeeData extends Data
{
    public function __construct(
        #[Required]
        public readonly string $effective_date,

        #[Nullable]
        public readonly ?string $note,

        #[Nullable]
        public readonly ?int $job_title_id,

        #[Nullable]
        public readonly ?int $department_id,

        #[Nullable]
        public readonly ?int $branch_id,

        #[Nullable]
        public readonly ?int $manager_id,

        #[Nullable]
        public readonly ?float $salary_base,

        #[Nullable]
        public readonly ?string $salary_currency,
    ) {}

    public static function rules(): array
    {
        $orgId     = TenantContext::getOrganizationId();
        $employeeId = request()->route('employee')?->id;

        return [
            'effective_date'  => ['required', 'date'],
            'job_title_id'    => ['nullable', 'integer', Rule::exists('job_titles', 'id')->where('organization_id', $orgId)],
            'department_id'   => ['nullable', 'integer', Rule::exists('departments', 'id')->where('organization_id', $orgId)],
            'branch_id'       => ['nullable', 'integer', Rule::exists('branches', 'id')->where('organization_id', $orgId)],
            'manager_id'      => ['nullable', 'integer', Rule::exists('employees', 'id')->where('organization_id', $orgId)->ignore($employeeId)],
            'salary_base'     => ['nullable', 'numeric', 'min:0'],
            'salary_currency' => ['nullable', 'string', 'size:3'],
        ];
    }
}

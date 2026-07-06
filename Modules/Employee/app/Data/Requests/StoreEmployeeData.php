<?php

namespace Modules\Employee\Data\Requests;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Validation\Rule;
use Modules\Employee\Enums\EmployeeStatus;
use Modules\Employee\Enums\EmploymentType;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class StoreEmployeeData extends Data
{
    public function __construct(
        public readonly int $organization_id,

        #[Required, StringType, Max(255)]
        public readonly string $full_name,

        #[Required, StringType, Max(50)]
        public readonly string $employee_code,

        #[Required, Email, Max(255)]
        public readonly string $email,

        #[Required]
        public readonly int $branch_id,

        #[Required]
        public readonly int $department_id,

        public readonly EmployeeStatus $status,

        public readonly EmploymentType $employment_type,

        #[Nullable]
        public readonly ?int $job_title_id,

        #[Nullable]
        public readonly ?int $manager_id,

        #[Nullable]
        public readonly ?int $user_id,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $phone,

        #[Nullable, StringType]
        public readonly ?string $gender,

        #[Nullable]
        public readonly ?string $date_of_birth,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $national_id,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $tax_code,

        #[Nullable, StringType, Max(10)]
        public readonly ?string $locale,

        #[Nullable, StringType, Max(500)]
        public readonly ?string $avatar_url,

        #[Nullable]
        public readonly ?string $hired_at,

        #[Nullable]
        public readonly ?string $probation_end_date,

        #[Nullable]
        public readonly ?string $contract_start,

        #[Nullable]
        public readonly ?string $contract_end,

        #[Nullable, StringType, Max(150)]
        public readonly ?string $personal_email,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $national_id_issued,

        #[Nullable, StringType, Max(20)]
        public readonly ?string $work_location,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        // Dùng organization_id thực sự được submit (form cho phép super-admin/tài khoản
        // không khoá cứng 1 tổ chức chọn tổ chức khác để tạo nhân viên) — không phải
        // TenantContext::getOrganizationId() (tổ chức hiện tại của session), vì 2 giá trị
        // này lệch nhau chính là nguyên nhân "The selected branch/department id is invalid."
        // dù branch/department chọn trên form là hợp lệ, thuộc đúng tổ chức đã chọn.
        $orgId = $context->payload['organization_id'] ?? TenantContext::getOrganizationId();

        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'employee_code' => [
                'required', 'string', 'max:50',
                Rule::unique('employees', 'employee_code')
                    ->where('organization_id', $orgId)
                    ->whereNull('deleted_at'),
            ],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('employees', 'email')
                    ->where('organization_id', $orgId)
                    ->whereNull('deleted_at'),
            ],
            'user_id' => [
                'nullable', 'integer',
                Rule::unique('employees', 'user_id')
                    ->where('organization_id', $orgId)
                    ->whereNull('deleted_at'),
                Rule::exists('users', 'id'),
            ],
            'branch_id'     => ['required', 'integer', Rule::exists('branches', 'id')->where('organization_id', $orgId)],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('organization_id', $orgId)],
            'job_title_id'  => ['nullable', 'integer', Rule::exists('job_titles', 'id')->where('organization_id', $orgId)],
            'manager_id'    => ['nullable', 'integer', Rule::exists('employees', 'id')->where('organization_id', $orgId)],
            'gender'              => ['nullable', 'string', 'in:male,female,other'],
            'date_of_birth'       => ['nullable', 'date'],
            'hired_at'            => ['nullable', 'date'],
            'probation_end_date'  => ['nullable', 'date'],
            'contract_start'      => ['nullable', 'date'],
            'contract_end'        => ['nullable', 'date', 'after_or_equal:contract_start'],
            'national_id_issued'  => ['nullable', 'date'],
            'work_location'       => ['nullable', 'string', 'in:office,remote,hybrid'],
        ];
    }
}

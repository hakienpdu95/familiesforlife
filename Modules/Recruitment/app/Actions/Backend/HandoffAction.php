<?php

namespace Modules\Recruitment\Actions\Backend;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\EmployeeDepartment;
use Modules\Employee\Models\EmployeeHistory;
use Modules\Recruitment\Models\RcOffer;

class HandoffAction
{
    use AsAction;

    public function handle(RcOffer $offer): Employee
    {
        $offer->load(['application.candidate', 'application']);
        $application = $offer->application;
        $candidate   = $application->candidate;
        $orgId       = TenantContext::getOrganizationId();

        // BR-RC-006: Idempotent — skip nếu employee email đã tồn tại trong org
        $existing = Employee::withoutGlobalScope('tenant')
            ->where('organization_id', $orgId)
            ->where('email', $candidate->email)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($offer, $application, $candidate, $orgId) {
            // 1. Resolve department + branch_id
            $departmentId = $this->resolveDepartmentId($candidate);
            $branchId     = $this->resolveBranchId($departmentId, $orgId);

            // Resolve job_title_id từ jp_job_post nếu có
            $jobTitleId   = $this->resolveJobTitleId($application->jp_job_post_id);

            $startDate      = $offer->start_date;
            $probationEnd   = $startDate->copy()->addDays($offer->probation_days);
            $employeeCode   = $this->generateEmployeeCode($orgId);

            // 2. Tạo employee
            $employee = Employee::create([
                'organization_id'    => $orgId,
                'branch_id'          => $branchId,
                'department_id'      => $departmentId,
                'job_title_id'       => $jobTitleId,
                'employee_code'      => $employeeCode,
                'full_name'          => $candidate->full_name,
                'email'              => $candidate->email,
                'phone'              => $candidate->phone,
                'gender'             => $candidate->gender,
                'date_of_birth'      => $candidate->date_of_birth,
                'status'             => 'probation',
                'employment_type'    => 'full_time',
                'hired_at'           => $startDate,
                'probation_end_date' => $probationEnd,
                'salary_base'        => $offer->salary_offered,
                'salary_currency'    => $offer->currency,
                'created_by'         => auth()->id(),
                'updated_by'         => auth()->id(),
            ]);

            // 3. employee_departments (primary)
            EmployeeDepartment::create([
                'employee_id'   => $employee->id,
                'department_id' => $departmentId,
                'is_primary'    => true,
                'joined_at'     => $startDate->toDateString(),
            ]);

            // 4. employee_history (hire)
            EmployeeHistory::create([
                'organization_id'     => $orgId,
                'employee_id'         => $employee->id,
                'changed_by'          => auth()->id(),
                'change_type'         => 'hire',
                'new_branch_id'       => $branchId,
                'new_department_id'   => $departmentId,
                'new_job_title_id'    => $jobTitleId,
                'new_status'          => 'probation',
                'new_employment_type' => 'full_time',
                'new_salary_base'     => $offer->salary_offered,
                'effective_date'      => $startDate->toDateString(),
                'note'                => 'Hire từ Recruitment — offer #' . $offer->uuid,
            ]);

            // 5. UPDATE application + candidate
            $application->update(['status' => 'hired']);
            $application->candidate->update(['status' => 'hired']);

            // 6. Cập nhật jp_job_posts.hired_count nếu có jp_job_post_id (soft ref)
            if ($application->jp_job_post_id) {
                DB::table('jp_job_posts')
                    ->where('uuid', $application->jp_job_post_id)
                    ->increment('hired_count');
            }

            return $employee;
        });
    }

    private function resolveDepartmentId(mixed $candidate): int
    {
        // Tìm department đầu tiên đang active — fallback nếu candidate không có department
        return DB::table('departments')
            ->where('organization_id', TenantContext::getOrganizationId())
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->value('id') ?? 1;
    }

    private function resolveBranchId(?int $departmentId, int $orgId): int
    {
        // Ưu tiên: department.branch_id → fallback org first active branch
        if ($departmentId) {
            $branchId = DB::table('departments')
                ->where('id', $departmentId)
                ->value('branch_id');
            if ($branchId) {
                return $branchId;
            }
        }

        return DB::table('branches')
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->orderBy('id')
            ->value('id') ?? 1;
    }

    private function resolveJobTitleId(?string $jpJobPostId): ?int
    {
        if (!$jpJobPostId) {
            return null;
        }

        return DB::table('jp_job_posts')
            ->where('uuid', $jpJobPostId)
            ->value('job_title_id');
    }

    private function generateEmployeeCode(int $orgId): string
    {
        $count = Employee::withoutGlobalScope('tenant')
            ->where('organization_id', $orgId)
            ->count();

        return 'EMP' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}

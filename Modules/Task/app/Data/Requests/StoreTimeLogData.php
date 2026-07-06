<?php

namespace Modules\Task\Data\Requests;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;

class StoreTimeLogData extends Data
{
    public function __construct(
        public readonly int $task_id,
        public readonly int $employee_id,
        public readonly float $hours,
        public readonly string $log_date,
        public readonly ?string $description,
        public readonly bool $is_billable = true,
    ) {}

    public static function rules(): array
    {
        $orgId = TenantContext::getOrganizationId();

        return [
            'task_id'     => ['required', 'integer', 'exists:tasks,id'],
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('organization_id', $orgId)],
            'hours'       => ['required', 'numeric', 'gt:0', 'max:24'],
            'log_date'    => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_billable' => ['boolean'],
        ];
    }
}

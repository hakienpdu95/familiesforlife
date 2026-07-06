<?php

namespace Modules\Task\Data\Requests;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Validation\Rule;
use Modules\Task\Enums\TaskPriority;
use Modules\Task\Enums\TaskStatus;
use Modules\Task\Enums\TaskType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class StoreTaskData extends Data
{
    public function __construct(
        #[Nullable]
        public readonly ?int $organization_id,

        #[Required]
        public readonly int $project_id,

        #[Required, StringType, Max(500)]
        public readonly string $title,

        public readonly TaskType $task_type,

        public readonly TaskStatus $status,

        public readonly TaskPriority $priority,

        #[Nullable]
        public readonly ?int $parent_id,

        #[Nullable]
        public readonly ?int $employee_id,

        #[Nullable, StringType]
        public readonly ?string $description,

        #[Nullable]
        public readonly ?int $story_points,

        #[Nullable, StringType]
        public readonly ?string $start_date,

        #[Nullable, StringType]
        public readonly ?string $due_date,

        #[Nullable, StringType]
        public readonly ?string $estimated_hours,

        public readonly array $label_ids = [],
    ) {}

    public static function rules(): array
    {
        $userOrgId = auth()->user()->organization_id;
        $orgId = $userOrgId
            ?: (request()->integer('organization_id') ?: TenantContext::getOrganizationId());

        return [
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'project_id'      => ['required', 'integer', Rule::exists('projects', 'id')->where('organization_id', $orgId)],
            'title'           => ['required', 'string', 'max:500'],
            'task_type'       => ['required', 'string', Rule::in(array_column(TaskType::cases(), 'value'))],
            'status'          => ['required', 'string', Rule::in(array_column(TaskStatus::cases(), 'value'))],
            'priority'        => ['required', 'string', Rule::in(array_column(TaskPriority::cases(), 'value'))],
            'parent_id'       => ['nullable', 'integer', 'exists:tasks,id'],
            'employee_id'     => ['nullable', 'integer', Rule::exists('employees', 'id')->where('organization_id', $orgId)],
            'description'     => ['nullable', 'string'],
            'story_points'    => ['nullable', 'integer', 'between:1,21'],
            'start_date'      => ['nullable', 'date_format:Y-m-d'],
            'due_date'        => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'label_ids'       => ['nullable', 'array'],
            'label_ids.*'     => ['integer', 'exists:task_labels,id'],
        ];
    }
}

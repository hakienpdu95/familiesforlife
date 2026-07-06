<?php

namespace Modules\Task\Data\Requests;

use Illuminate\Validation\Rule;
use Modules\Task\Enums\TaskPriority;
use Modules\Task\Enums\TaskStatus;
use Modules\Task\Enums\TaskType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateTaskData extends Data
{
    public function __construct(
        #[Required, StringType, Max(500)]
        public readonly string $title,

        public readonly TaskType $task_type,

        public readonly TaskStatus $status,

        public readonly TaskPriority $priority,

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
        return [
            'title'           => ['required', 'string', 'max:500'],
            'task_type'       => ['required', 'string', Rule::in(array_column(TaskType::cases(), 'value'))],
            'status'          => ['required', 'string', Rule::in(array_column(TaskStatus::cases(), 'value'))],
            'priority'        => ['required', 'string', Rule::in(array_column(TaskPriority::cases(), 'value'))],
            'employee_id'     => ['nullable', 'integer', 'exists:employees,id'],
            'description'     => ['nullable', 'string'],
            'story_points'    => ['nullable', 'integer', 'between:1,21'],
            'start_date'      => ['nullable', 'date_format:Y-m-d'],
            'due_date'        => ['nullable', 'date_format:Y-m-d'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'label_ids'       => ['nullable', 'array'],
            'label_ids.*'     => ['integer', 'exists:task_labels,id'],
        ];
    }
}

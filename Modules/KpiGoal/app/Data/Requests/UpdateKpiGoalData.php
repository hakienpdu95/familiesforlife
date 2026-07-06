<?php

namespace Modules\KpiGoal\Data\Requests;

use Modules\KpiGoal\Enums\KpiDirection;
use Spatie\LaravelData\Data;

class UpdateKpiGoalData extends Data
{
    public function __construct(
        public readonly string       $title,
        public readonly float        $target_value,
        public readonly KpiDirection $direction,
        public readonly int          $weight_percent,
        public readonly string       $cycle_label,
        public readonly string       $cycle_start,
        public readonly string       $cycle_end,
        public readonly ?int         $parent_goal_id,
        public readonly ?string      $description,
        public readonly ?string      $unit,
    ) {}

    public static function rules(): array
    {
        return [
            'title'          => ['required', 'string', 'max:300'],
            'target_value'   => ['required', 'numeric'],
            'direction'      => ['required', 'string'],
            'weight_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'cycle_label'    => ['required', 'string', 'max:30'],
            'cycle_start'    => ['required', 'date'],
            'cycle_end'      => ['required', 'date', 'after:cycle_start'],
            'description'    => ['nullable', 'string', 'max:2000'],
            'unit'           => ['nullable', 'string', 'max:30'],
        ];
    }
}

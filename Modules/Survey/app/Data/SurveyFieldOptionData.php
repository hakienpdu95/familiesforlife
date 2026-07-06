<?php

namespace Modules\Survey\Data;

use Modules\Survey\Models\SurveyFieldOption;
use Spatie\LaravelData\Data;

class SurveyFieldOptionData extends Data
{
    public function __construct(
        public readonly int    $id,
        public readonly string $option_value,
        public readonly string $label,
        public readonly int    $sort_order,
        public readonly bool   $is_other,
    ) {}

    public static function fromModel(SurveyFieldOption $option): self
    {
        return new self(
            id:           $option->id,
            option_value: $option->option_value,
            label:        $option->label,
            sort_order:   $option->sort_order,
            is_other:     $option->is_other,
        );
    }
}

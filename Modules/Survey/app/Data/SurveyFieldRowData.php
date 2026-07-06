<?php

namespace Modules\Survey\Data;

use Modules\Survey\Models\SurveyFieldRow;
use Spatie\LaravelData\Data;

class SurveyFieldRowData extends Data
{
    public function __construct(
        public readonly int    $id,
        public readonly string $row_key,
        public readonly string $label,
        public readonly int    $sort_order,
    ) {}

    public static function fromModel(SurveyFieldRow $row): self
    {
        return new self(
            id:         $row->id,
            row_key:    $row->row_key,
            label:      $row->label,
            sort_order: $row->sort_order,
        );
    }
}

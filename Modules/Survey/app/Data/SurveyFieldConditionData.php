<?php

namespace Modules\Survey\Data;

use Modules\Survey\Models\SurveyFieldCondition;
use Spatie\LaravelData\Data;

class SurveyFieldConditionData extends Data
{
    public function __construct(
        public readonly string $depends_on_field_key,
        public readonly string $operator,
        public readonly mixed  $trigger_value,
        public readonly string $action,
    ) {}

    public static function fromModel(SurveyFieldCondition $condition, string $dependsOnFieldKey): self
    {
        return new self(
            depends_on_field_key: $dependsOnFieldKey,
            operator:             $condition->operator,
            trigger_value:        $condition->trigger_value,
            action:               $condition->action,
        );
    }
}

<?php

namespace Modules\Survey\Data;

use Modules\Survey\Enums\FieldType;
use Modules\Survey\Enums\ValueKind;
use Modules\Survey\Models\SurveyField;
use Spatie\LaravelData\Data;


class SurveyFieldData extends Data
{
    public function __construct(
        public readonly int       $id,
        public readonly ?int      $parent_field_id,
        public readonly string    $field_key,
        public readonly string    $label,
        public readonly FieldType $field_type,
        public readonly ValueKind $value_kind,
        public readonly bool      $is_required,
        public readonly int       $sort_order,
        public readonly ?int      $rule_min,
        public readonly ?int      $rule_max,
        public readonly ?int      $rule_max_select,
        public readonly ?string   $placeholder,
        /** @var SurveyFieldOptionData[] */
        public readonly array     $options,
        /** @var SurveyFieldConditionData[] */
        public readonly array     $conditions = [],
        /** @var SurveyFieldRowData[] */
        public readonly array     $rows = [],
    ) {}

    /**
     * @param  array<int, string>  $fieldKeyMap  [field_id => field_key] for resolving depends_on_field_key
     */
    public static function fromModel(SurveyField $field, array $fieldKeyMap = []): self
    {
        $conditions = $field->relationLoaded('conditions')
            ? $field->conditions
                ->map(function ($c) use ($fieldKeyMap) {
                    $depKey = $fieldKeyMap[$c->depends_on_field_id] ?? (string) $c->depends_on_field_id;
                    return SurveyFieldConditionData::fromModel($c, $depKey);
                })
                ->all()
            : [];

        return new self(
            id:              $field->id,
            parent_field_id: $field->parent_field_id,
            field_key:       $field->field_key,
            label:           $field->label,
            field_type:      $field->field_type,
            value_kind:      $field->value_kind,
            is_required:     $field->is_required,
            sort_order:      $field->sort_order,
            rule_min:        $field->rule_min,
            rule_max:        $field->rule_max,
            rule_max_select: $field->rule_max_select,
            placeholder:     $field->placeholder,
            options:         $field->options
                ->map(fn($o) => SurveyFieldOptionData::fromModel($o))
                ->all(),
            conditions:      $conditions,
            rows:            $field->relationLoaded('rows')
                ? $field->rows->map(fn($r) => SurveyFieldRowData::fromModel($r))->all()
                : [],
        );
    }
}

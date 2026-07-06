<?php

namespace Modules\Survey\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Survey\Actions\BuildSurveySchemaAction;
use Modules\Survey\Data\FieldFormData;
use Modules\Survey\Enums\FieldType;
use Modules\Survey\Models\Survey;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Survey\Models\SurveyField;

class CreateFieldAction
{
    use AsAction;

    public function handle(Survey $survey, FieldFormData $data): SurveyField
    {
        $fieldKey = $this->generateKey($survey, $data->field_type);

        $maxOrder = SurveyField::forSurvey($survey->id)
            ->when($data->section_id, fn ($q) => $q->where('section_id', $data->section_id))
            ->max('sort_order') ?? 0;

        $field = new SurveyField([
            'survey_id'       => $survey->id,
            'section_id'      => $data->section_id,
            'parent_field_id' => $data->parent_field_id,
            'field_key'       => $fieldKey,
            'label'           => $data->label,
            'field_type'      => $data->field_type,
            'value_kind'      => $data->field_type->valueKind(),
            'is_required'     => $data->is_required,
            'sort_order'      => $maxOrder + 1,
            'rule_min'        => $data->rule_min,
            'rule_max'        => $data->rule_max,
            'rule_max_select' => $data->rule_max_select,
            'placeholder'     => $data->placeholder,
        ]);
        $field->is_active = true;
        $field->save();

        ActivityLogger::info('Survey', 'field_created', $field, ['survey_id' => $survey->id, 'field_key' => $field->field_key]);

        BuildSurveySchemaAction::purgeCache($survey->slug);

        return $field->load('options');
    }

    private function generateKey(Survey $survey, FieldType $type): string
    {
        $prefix = match ($type) {
            FieldType::Text     => 'txt',
            FieldType::Textarea => 'ta',
            FieldType::Number   => 'num',
            FieldType::Select   => 'sel',
            FieldType::Radio    => 'rad',
            FieldType::Checkbox => 'chk',
            FieldType::Rating   => 'rat',
            FieldType::Date     => 'dt',
            FieldType::Boolean  => 'bool',
        };

        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';

        do {
            $rand = '';
            for ($i = 0; $i < 24; $i++) {
                $rand .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $key = "{$prefix}_{$rand}";
        } while (SurveyField::forSurvey($survey->id)->where('field_key', $key)->exists());

        return $key;
    }
}

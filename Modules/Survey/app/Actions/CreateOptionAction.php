<?php

namespace Modules\Survey\Actions;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Survey\Actions\BuildSurveySchemaAction;
use Modules\Survey\Data\OptionFormData;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyField;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Survey\Models\SurveyFieldOption;

class CreateOptionAction
{
    use AsAction;

    public function handle(Survey $survey, SurveyField $field, OptionFormData $data): SurveyFieldOption
    {
        // option_value unique trong cùng field
        $exists = SurveyFieldOption::forField($field->id)
            ->where('option_value', $data->option_value)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'option_value' => "option_value '{$data->option_value}' đã tồn tại trong field này.",
            ]);
        }

        $maxOrder = SurveyFieldOption::forField($field->id)->max('sort_order') ?? 0;

        $option = SurveyFieldOption::create([
            'field_id'     => $field->id,
            'option_value' => $data->option_value,
            'label'        => $data->label,
            'sort_order'   => $maxOrder + 1,
            'is_other'     => $data->is_other,
        ]);

        ActivityLogger::info('Survey', 'option_created', $option, ['field_id' => $field->id, 'option_value' => $option->option_value]);

        BuildSurveySchemaAction::purgeCache($survey->slug);

        return $option;
    }
}

<?php

namespace Modules\Survey\Actions;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Survey\Actions\BuildSurveySchemaAction;
use Modules\Survey\Data\OptionFormData;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyField;
use Modules\Survey\Models\SurveyFieldOption;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Survey\Support\GuardsSurveyIntegrity;

class UpdateOptionAction
{
    use AsAction;
    use GuardsSurveyIntegrity;

    public function handle(Survey $survey, SurveyField $field, SurveyFieldOption $option, OptionFormData $data): SurveyFieldOption
    {
        $this->guardLockedSurvey($survey);

        // option_value bất biến sau khi survey active (nếu đã có response)
        $payload = ['label' => $data->label, 'is_other' => $data->is_other];

        if ($survey->status->value !== 1) {
            if ($data->option_value !== $option->option_value) {
                $exists = SurveyFieldOption::forField($field->id)
                    ->where('option_value', $data->option_value)
                    ->where('id', '!=', $option->id)
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'option_value' => "option_value '{$data->option_value}' đã tồn tại.",
                    ]);
                }
            }

            $payload['option_value'] = $data->option_value;
        }

        $option->update($payload);

        ActivityLogger::info('Survey', 'option_updated', $option, []);

        BuildSurveySchemaAction::purgeCache($survey->slug);

        return $option->fresh();
    }
}

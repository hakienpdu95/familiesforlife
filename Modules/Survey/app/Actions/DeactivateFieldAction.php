<?php

namespace Modules\Survey\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyField;

class DeactivateFieldAction
{
    use AsAction;

    public function handle(SurveyField $field): SurveyField
    {
        $field->is_active = !$field->is_active;
        $field->save();

        $props = ['field_key' => $field->field_key, 'is_active' => $field->is_active];
        if ($field->is_active) {
            ActivityLogger::info('Survey', 'field_activated', $field, $props);
        } else {
            ActivityLogger::warning('Survey', 'field_deactivated', $field, $props);
        }

        $slug = Survey::where('id', $field->survey_id)->value('slug');
        if ($slug) {
            BuildSurveySchemaAction::purgeCache($slug);
        }

        return $field;
    }
}

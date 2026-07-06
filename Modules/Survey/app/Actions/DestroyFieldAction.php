<?php

namespace Modules\Survey\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Survey\Actions\BuildSurveySchemaAction;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyField;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Survey\Support\GuardsSurveyIntegrity;

class DestroyFieldAction
{
    use AsAction;
    use GuardsSurveyIntegrity;

    public function handle(Survey $survey, SurveyField $field): void
    {
        $this->guardLockedSurvey($survey);

        $label = $field->label;
        $key   = $field->field_key;

        $field->delete();

        ActivityLogger::warning('Survey', 'field_deleted', null, ['field_key' => $key, 'label' => $label]);

        BuildSurveySchemaAction::purgeCache($survey->slug);
    }
}

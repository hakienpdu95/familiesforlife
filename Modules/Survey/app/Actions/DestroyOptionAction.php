<?php

namespace Modules\Survey\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyFieldOption;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Survey\Support\GuardsSurveyIntegrity;

class DestroyOptionAction
{
    use AsAction;
    use GuardsSurveyIntegrity;

    public function handle(Survey $survey, SurveyFieldOption $option): void
    {
        $this->guardLockedSurvey($survey);

        $value = $option->option_value;
        $option->delete();

        ActivityLogger::warning('Survey', 'option_deleted', null, ['option_value' => $value]);

        BuildSurveySchemaAction::purgeCache($survey->slug);
    }
}

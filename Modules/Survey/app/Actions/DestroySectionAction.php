<?php

namespace Modules\Survey\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Survey\Actions\BuildSurveySchemaAction;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveySection;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Survey\Support\GuardsSurveyIntegrity;

class DestroySectionAction
{
    use AsAction;
    use GuardsSurveyIntegrity;

    public function handle(Survey $survey, SurveySection $section): void
    {
        $this->guardLockedSurvey($survey);

        $title = $section->title;
        $section->delete();

        ActivityLogger::warning('Survey', 'section_deleted', null, ['survey_id' => $survey->id, 'title' => $title]);

        BuildSurveySchemaAction::purgeCache($survey->slug);
    }
}

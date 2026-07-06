<?php

namespace Modules\Survey\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Survey\Actions\BuildSurveySchemaAction;
use Modules\Survey\Data\SectionFormData;
use Modules\Survey\Models\Survey;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Survey\Models\SurveySection;

class UpdateSectionAction
{
    use AsAction;

    public function handle(Survey $survey, SurveySection $section, SectionFormData $data): SurveySection
    {
        $section->update([
            'title' => $data->title,
            'icon'  => $data->icon,
        ]);

        ActivityLogger::info('Survey', 'section_updated', $section, ['title' => $data->title]);

        BuildSurveySchemaAction::purgeCache($survey->slug);

        return $section->fresh();
    }
}

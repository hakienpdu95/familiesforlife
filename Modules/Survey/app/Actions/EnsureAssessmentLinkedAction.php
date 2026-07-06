<?php

namespace Modules\Survey\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Assessment\Models\Assessment;
use Modules\Survey\Models\Survey;

class EnsureAssessmentLinkedAction
{
    use AsAction;

    public function handle(Survey $survey): string
    {
        if ($survey->assessment_code) {
            return $survey->assessment_code;
        }

        // Derive code từ slug — dùng đúng quy ước để backward compatible với data cũ
        $code = str_replace('-', '_', $survey->slug);

        Assessment::firstOrCreate(
            ['assessment_code' => $code],
            [
                'name'                => $survey->title,
                'aggregation_model'   => 'weighted_domain',
                'classification_type' => 'score_band',
                'has_scoring'         => true,
                'is_active'           => true,
                'source_type'         => 'survey_linked',
                'source_ref'          => $survey->slug,
            ]
        );

        $survey->update(['assessment_code' => $code]);

        return $code;
    }
}

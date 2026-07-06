<?php

namespace Modules\Survey\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Survey\Actions\EnsureAssessmentLinkedAction;
use Modules\Survey\Models\Survey;

class SurveyScoringRedirectController extends Controller
{
    public function redirect(Survey $survey)
    {
        $this->authorize('survey.update');

        $code = EnsureAssessmentLinkedAction::run($survey);

        return redirect()->route('assessments.config.index', $code);
    }
}

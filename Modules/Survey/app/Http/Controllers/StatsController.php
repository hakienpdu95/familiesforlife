<?php

namespace Modules\Survey\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Assessment\Models\AssessmentDomain;
use Modules\Assessment\Models\AssessmentResult;
use Modules\Assessment\Models\MaturityLevel;
use Modules\Assessment\Models\ResultDomainScore;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyField;
use Modules\Survey\Services\SurveyStatsService;

class StatsController extends Controller
{
    public function index(Survey $survey, SurveyStatsService $service)
    {
        $this->authorize('survey.view_responses');

        $stats = $service->forSurvey($survey);
        $byDay = $service->totalByDay($survey, 30);

        $scoringData = null;
        if ($survey->hasScoring()) {
            $code    = $survey->assessment_code;
            $results = AssessmentResult::forSurvey($survey->id);

            $scoringData = [
                'total_scored'          => (clone $results)->count(),
                'avg_overall'           => (clone $results)->avg('overall_score'),
                'maturity_distribution' => (clone $results)
                    ->selectRaw('maturity_level, COUNT(*) as count')
                    ->groupBy('maturity_level')
                    ->pluck('count', 'maturity_level'),
                'avg_domain_scores'     => ResultDomainScore::whereHas(
                    'result', fn ($q) => $q->forSurvey($survey->id)
                )
                    ->selectRaw('domain_code, ROUND(AVG(normalized_score), 2) as avg_score')
                    ->groupBy('domain_code')
                    ->pluck('avg_score', 'domain_code'),
                'maturity_levels'       => MaturityLevel::forAssessment($code)->ordered()->get(),
                'domains'               => AssessmentDomain::forAssessment($code)->ordered()->get(),
            ];
        }

        return view('survey::stats.index', compact('survey', 'stats', 'byDay', 'scoringData'));
    }

    /**
     * GET /{survey}/stats/fields/{field} — per-field breakdown for chart rendering.
     * Returns JSON; consumed by Alpine.js on the stats page.
     */
    public function fieldStats(Survey $survey, SurveyField $field, SurveyStatsService $service): JsonResponse
    {
        $this->authorize('survey.view_responses');

        if ($field->survey_id !== $survey->id) {
            abort(404);
        }

        $breakdown = $service->fieldBreakdown($survey->id, $field->id);

        return response()->json([
            'field_key'  => $field->field_key,
            'label'      => $field->label,
            'field_type' => $field->field_type->name,
            'stats'      => $breakdown,
        ]);
    }
}

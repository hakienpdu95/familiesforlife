<?php

namespace Modules\Assessment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Assessment\Models\AssessmentDomain;
use Modules\Assessment\Models\AssessmentResult;
use Modules\Assessment\Models\MaturityLevel;
use Modules\Assessment\Models\PainPointRule;
use Modules\Assessment\Models\RecommendationRule;
use Modules\Assessment\Models\ScoreBand;

class AssessmentPublicResultController extends Controller
{
    public function show(Request $request, string $token): View
    {
        /** @var AssessmentResult $result */
        $result = $request->attributes->get('assessment_result');

        $result->load([
            'domainScores',
            'painPoints',
            'recommendations',
            'roadmapPhases.phase.milestones',
            'classification',
        ]);

        $code = $result->assessment_code;

        $domainLabels = AssessmentDomain::where('assessment_code', $code)
            ->pluck('label', 'domain_code');

        $painLabels = PainPointRule::where('assessment_code', $code)
            ->pluck('label', 'pain_point_code');

        $recLabels = RecommendationRule::where('assessment_code', $code)
            ->get()
            ->keyBy('recommendation_code');

        $bandInfo = ScoreBand::where('assessment_code', $code)
            ->where('band_code', $result->maturity_level)
            ->first();

        $maturityInfo = MaturityLevel::where('assessment_code', $code)
            ->where('level_code', $result->maturity_level)
            ->first();

        return view('assessment::public.result', compact(
            'result',
            'domainLabels',
            'painLabels',
            'recLabels',
            'bandInfo',
            'maturityInfo',
        ));
    }
}

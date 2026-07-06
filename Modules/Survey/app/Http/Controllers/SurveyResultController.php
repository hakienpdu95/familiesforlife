<?php

namespace Modules\Survey\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Assessment\Models\AssessmentDomain;
use Modules\Assessment\Models\AssessmentResult;
use Modules\Assessment\Models\MaturityLevel;
use Modules\Assessment\Models\PainPointRule;
use Modules\Assessment\Models\RecommendationRule;
use Modules\Assessment\Models\ResultDomainScore;
use Modules\Assessment\Models\ScoreBand;
use Modules\Assessment\Models\ScoringFeedback;
use Modules\Survey\Actions\CalculateSurveyScoreAction;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyResponse;

class SurveyResultController extends Controller
{
    // ── T1 (150A): Trang kết quả HTML public cho respondent ──────────────────

    public function publicResult(Request $request, string $slug): View
    {
        /** @var Survey $survey */
        $survey = $request->attributes->get('survey');

        abort_if(! $survey->hasScoring(), 404, 'Khảo sát này không có kết quả chấm điểm.');

        $ref   = trim((string) $request->query('ref', ''));
        $token = (string) $request->query('token', '');

        if ($ref === '') {
            return view('survey::results.public', [
                'survey'       => $survey,
                'response'     => null,
                'result'       => null,
                'processing'   => false,
                'notFound'     => true,
                'ref'          => '',
                'token'        => $token,
                'recLabels'    => collect(),
                'painLabels'   => collect(),
                'domainLabels' => collect(),
                'maturityInfo' => null,
            ]);
        }

        $response = SurveyResponse::where('survey_id', $survey->id)
            ->where('respondent_ref', $ref)
            ->latest()
            ->first();

        if (! $response) {
            return view('survey::results.public', [
                'survey'       => $survey,
                'response'     => null,
                'result'       => null,
                'processing'   => false,
                'notFound'     => true,
                'ref'          => $ref,
                'token'        => $token,
                'recLabels'    => collect(),
                'painLabels'   => collect(),
                'domainLabels' => collect(),
                'maturityInfo' => null,
            ]);
        }

        $result = AssessmentResult::forResponse($response->id)
            ->with([
                'domainScores',
                'signalFlags',
                'painPoints',
                'recommendations',
                'roadmapPhases.phase.milestones',
                'classification',
            ])
            ->first();

        $assessmentCode = $survey->assessment_code;
        $recLabels      = collect();
        $painLabels     = collect();
        $maturityInfo   = null;

        if ($result) {
            $recLabels = RecommendationRule::where('assessment_code', $assessmentCode)
                ->get(['recommendation_code', 'label', 'description'])
                ->keyBy('recommendation_code');

            $painLabels = PainPointRule::where('assessment_code', $assessmentCode)
                ->pluck('label', 'pain_point_code');

            $maturityInfo = ScoreBand::forAssessment($assessmentCode)
                ->where('band_code', $result->maturity_level)
                ->first()
                ?? MaturityLevel::where('assessment_code', $assessmentCode)
                    ->where('level_code', $result->maturity_level)
                    ->first();
        }

        $domainLabels = AssessmentDomain::forAssessment($assessmentCode)
            ->pluck('label', 'domain_code');

        return view('survey::results.public', [
            'survey'       => $survey,
            'response'     => $response,
            'result'       => $result,
            'processing'   => $result === null,
            'notFound'     => false,
            'ref'          => $ref,
            'token'        => $token,
            'recLabels'    => $recLabels,
            'painLabels'   => $painLabels,
            'domainLabels' => $domainLabels,
            'maturityInfo' => $maturityInfo,
        ]);
    }

    // ── T6.2: Admin xem chi tiết một result ──────────────────────────────────

    public function show(Survey $survey, SurveyResponse $response): View
    {
        $this->authorize('survey.view_responses');
        $this->checkOwnership($response, $survey);

        $result = AssessmentResult::forResponse($response->id)
            ->with([
                'domainScores',
                'signalFlags',
                'painPoints',
                'recommendations',
                'roadmapPhases.phase.milestones',
            ])
            ->first();

        $recLabels = $result
            ? RecommendationRule::where('assessment_code', $result->assessment_code)
                ->pluck('label', 'recommendation_code')
            : collect();

        $domainLabels = $survey->assessment_code
            ? AssessmentDomain::forAssessment($survey->assessment_code)
                ->pluck('label', 'domain_code')
            : collect();

        $feedback = $result
            ? ScoringFeedback::where('result_id', $result->id)->first()
            : null;

        $bands = $this->loadBands($survey->assessment_code);

        return view('survey::results.show', compact(
            'survey', 'response', 'result', 'recLabels', 'domainLabels', 'feedback', 'bands'
        ));
    }

    // ── T6.3: Admin xem tổng hợp scoring toàn bộ responses ───────────────────

    public function summary(Survey $survey): View
    {
        $this->authorize('survey.view_responses');

        if (! $survey->hasScoring()) {
            abort(404, 'Survey này không có cấu hình chấm điểm.');
        }

        $assessmentCode = $survey->assessment_code;

        $maturityDistribution = AssessmentResult::forSurvey($survey->id)
            ->selectRaw('maturity_level, COUNT(*) as count')
            ->groupBy('maturity_level')
            ->pluck('count', 'maturity_level');

        $avgDomainScores = ResultDomainScore::whereHas(
            'result', fn ($q) => $q->forSurvey($survey->id)
        )
            ->selectRaw('domain_code, ROUND(AVG(normalized_score), 2) as avg_score')
            ->groupBy('domain_code')
            ->pluck('avg_score', 'domain_code');

        $avgOverall  = AssessmentResult::forSurvey($survey->id)->avg('overall_score');
        $totalScored = AssessmentResult::forSurvey($survey->id)->count();

        $maturityLevels = MaturityLevel::forAssessment($assessmentCode)->ordered()->get();
        $domains        = AssessmentDomain::forAssessment($assessmentCode)->ordered()->get();

        return view('survey::results.summary', compact(
            'survey',
            'maturityDistribution',
            'avgDomainScores',
            'avgOverall',
            'totalScored',
            'maturityLevels',
            'domains',
        ));
    }

    // ── T6.4: Admin force recalculate một result ──────────────────────────────

    public function recalculate(
        Survey $survey,
        SurveyResponse $response,
        CalculateSurveyScoreAction $action,
    ): JsonResponse {
        $this->authorize('survey.update');
        $this->checkOwnership($response, $survey);

        if (! $survey->hasScoring()) {
            return response()->json(['success' => false, 'message' => 'Survey không có cấu hình chấm điểm.'], 422);
        }

        $action->handle($response->id, force: true);

        return response()->json(['success' => true, 'message' => 'Đã tính lại điểm thành công.']);
    }

    // ── T6: Admin xác nhận actual_band ───────────────────────────────────────

    public function submitFeedback(
        Survey $survey,
        SurveyResponse $response,
        Request $request,
    ): JsonResponse {
        $this->authorize('survey.update');
        $this->checkOwnership($response, $survey);

        $result = AssessmentResult::forResponse($response->id)->first();
        if (! $result) {
            return response()->json(['success' => false, 'message' => 'Chưa có kết quả chấm điểm.'], 422);
        }

        $validCodes = $this->loadBands($result->assessment_code)->pluck('code')->all();

        $data = $request->validate([
            'actual_band' => ['required', 'string', 'max:60', Rule::in($validCodes)],
        ]);

        ScoringFeedback::where('result_id', $result->id)->update([
            'actual_band'  => $data['actual_band'],
            'is_processed' => false,
        ]);

        return response()->json(['success' => true, 'message' => 'Đã xác nhận band thực tế.']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** @return \Illuminate\Support\Collection<int, array{code:string,label:string}> */
    private function loadBands(?string $assessmentCode): \Illuminate\Support\Collection
    {
        if (! $assessmentCode) {
            return collect();
        }

        $bands = ScoreBand::forAssessment($assessmentCode)->ordered()
            ->get(['band_code', 'label'])
            ->map(fn ($b) => ['code' => $b->band_code, 'label' => $b->label]);

        if ($bands->isEmpty()) {
            $bands = MaturityLevel::where('assessment_code', $assessmentCode)->ordered()
                ->get(['level_code', 'label'])
                ->map(fn ($b) => ['code' => $b->level_code, 'label' => $b->label]);
        }

        return $bands;
    }

    private function checkOwnership(SurveyResponse $response, Survey $survey): void
    {
        if ($response->survey_id !== $survey->id) {
            abort(404);
        }
    }
}

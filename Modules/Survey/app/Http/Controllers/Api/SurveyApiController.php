<?php

namespace Modules\Survey\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Survey\Actions\BuildSurveySchemaAction;
use Modules\Survey\Actions\ExportSurveyResponsesAction;
use Modules\Survey\Actions\SubmitSurveyAction;
use Modules\Survey\Http\Requests\SubmitSurveyRequest;
use Modules\Assessment\Models\AssessmentResult;
use Modules\Assessment\Models\RecommendationRule;
use Modules\Survey\Models\SubmissionBehaviorLog;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyDraft;
use Modules\Survey\Models\SurveyResponse;
use Modules\Survey\Services\SurveyStatsService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SurveyApiController extends Controller
{
    public function schema(string $slug, BuildSurveySchemaAction $action): JsonResponse
    {
        return response()->json($action->handle($slug));
    }

    public function submit(
        string              $slug,
        SubmitSurveyRequest $request,
        SubmitSurveyAction  $action,
    ): JsonResponse {
        // Load by slug only — action kiểm tra status (draft/closed → 403)
        $survey = Survey::bySlug($slug)->firstOrFail();

        $responseId = $action->handle($survey, $request->toResponseData());

        // Increment token usage_count atomically after successful submit
        $token = $request->attributes->get('surveyToken');
        if ($token) {
            $token->increment('usage_count');
        }

        return response()->json(['response_id' => $responseId], 201);
    }

    public function stats(string $slug, SurveyStatsService $service): JsonResponse
    {
        $survey = Survey::active()->bySlug($slug)->firstOrFail();

        return response()->json($service->forSurvey($survey));
    }

    public function responses(
        string                      $slug,
        Request                     $request,
        ExportSurveyResponsesAction $exportAction,
    ): JsonResponse|StreamedResponse {
        $survey = Survey::active()->bySlug($slug)->firstOrFail();

        if ($request->boolean('export') || $request->query('export') === 'xlsx') {
            $result = $exportAction->handle(
                $survey,
                $request->query('respondent_ref'),
                $request->query('from'),
                $request->query('to'),
            );

            if (is_array($result)) {
                return response()->json([
                    'queued'       => true,
                    'queued_key'   => $result['queued_key'],
                ], 202);
            }

            return $result;
        }

        $responses = SurveyResponse::forSurvey($survey->id)
            ->complete()
            ->when($request->query('respondent_ref'), fn ($q) => $q->where('respondent_ref', $request->query('respondent_ref')))
            ->when($request->query('from'),           fn ($q) => $q->where('submitted_at', '>=', $request->query('from')))
            ->when($request->query('to'),             fn ($q) => $q->where('submitted_at', '<=', $request->query('to') . ' 23:59:59'))
            ->orderBy('submitted_at', 'desc')
            ->paginate(50);

        return response()->json($responses);
    }

    /**
     * T3 (Module 110) — Nhận batch behavior events từ frontend sau khi submit.
     *
     * POST /v1/surveys/{slug}/behavior
     * Body: { response_id, events: [{question_code?, event_type, event_value?, occurred_at}] }
     */
    public function behavior(string $slug, Request $request): JsonResponse
    {
        $survey = Survey::active()->bySlug($slug)->firstOrFail();

        $data = $request->validate([
            'response_id'            => 'required|integer|min:1',
            'events'                 => 'required|array|min:1|max:500',
            'events.*.question_code' => 'nullable|string|max:100',
            'events.*.event_type'    => 'required|string|max:30|in:question_focus,question_blur,answer_changed,answer_cleared,section_entered,time_spent',
            'events.*.event_value'   => 'nullable|string|max:255',
            'events.*.occurred_at'   => 'required|date',
        ]);

        $responseId = (int) $data['response_id'];

        // Security: response must belong to this survey
        $responseExists = SurveyResponse::where('id', $responseId)
            ->where('survey_id', $survey->id)
            ->exists();

        if (! $responseExists) {
            return response()->json(['error' => 'Response không thuộc survey này.'], 403);
        }

        // Sequence numbers continue from the last stored event
        $maxSeq = SubmissionBehaviorLog::where('response_id', $responseId)
            ->max('sequence_no') ?? 0;

        $rows = [];
        foreach ($data['events'] as $i => $event) {
            $rows[] = [
                'response_id'   => $responseId,
                'question_code' => $event['question_code'] ?? null,
                'event_type'    => $event['event_type'],
                'event_value'   => $event['event_value'] ?? null,
                'sequence_no'   => $maxSeq + $i + 1,
                'occurred_at'   => Carbon::parse($event['occurred_at'])->format('Y-m-d H:i:s'),
            ];
        }

        if (! empty($rows)) {
            SubmissionBehaviorLog::insert($rows);
        }

        return response()->json(['stored' => count($rows)], 201);
    }

    /**
     * T6.1 — Respondent xem kết quả của mình qua Bearer token + respondent_ref.
     *
     * GET /v1/surveys/{slug}/result?ref={email_or_phone}
     *
     * Middleware ValidateSurveyToken đã xác thực token thuộc đúng survey này.
     * Dùng respondent_ref (email/phone submitted khi nộp bài) để tìm response.
     * Trả về result mới nhất nếu có nhiều lần submit.
     */
    public function result(string $slug, Request $request): JsonResponse
    {
        $survey = Survey::active()->bySlug($slug)->firstOrFail();

        if (!$survey->hasScoring()) {
            return response()->json(['error' => 'Survey này không có chấm điểm.'], 422);
        }

        $responseId = $request->query('response_id');
        $ref        = $request->query('ref');

        if (!empty($responseId)) {
            $response = SurveyResponse::forSurvey($survey->id)
                ->complete()
                ->where('id', (int) $responseId)
                ->first();
        } elseif (!empty($ref)) {
            $response = SurveyResponse::forSurvey($survey->id)
                ->complete()
                ->where('respondent_ref', $ref)
                ->latest('submitted_at')
                ->first();
        } else {
            return response()->json(['error' => 'Tham số ?response_id= hoặc ?ref= là bắt buộc.'], 422);
        }

        if (!$response) {
            return response()->json(['error' => 'Không tìm thấy phản hồi cho thông tin này.'], 404);
        }

        $result = AssessmentResult::forResponse($response->id)
            ->with(['domainScores', 'signalFlags', 'painPoints', 'recommendations', 'roadmapPhases.phase.milestones'])
            ->first();

        if (!$result) {
            return response()->json(['message' => 'Kết quả chưa sẵn sàng, vui lòng thử lại sau.'], 202);
        }

        // Enrich recommendations với label từ config
        $recLabels = RecommendationRule::where('assessment_code', $result->assessment_code)
            ->pluck('label', 'recommendation_code');

        return response()->json([
            'overall_score'  => round($result->overall_score, 2),
            'maturity_level' => $result->maturity_level,
            'calculated_at'  => $result->calculated_at,
            'domain_scores'  => $result->domainScores->map(fn ($ds) => [
                'domain_code'      => $ds->domain_code,
                'raw'              => $ds->raw_score,
                'normalized'       => round($ds->normalized_score, 2),
            ])->values(),
            'signal_flags'   => $result->signalFlags->pluck('flag_value', 'flag_code'),
            'pain_points'    => $result->painPoints->pluck('pain_point_code')->values(),
            'recommendations' => $result->recommendations->map(fn ($r) => [
                'code'     => $r->recommendation_code,
                'label'    => $recLabels[$r->recommendation_code] ?? $r->recommendation_code,
                'priority' => $r->priority,
            ])->values(),
            'roadmap' => $result->roadmapPhases->map(fn ($rp) => [
                'phase_code'     => $rp->phase?->phase_code,
                'title'          => $rp->phase?->title,
                'duration_weeks' => $rp->phase?->duration_weeks,
                'milestones'     => $rp->phase?->milestones->pluck('title')->values() ?? [],
            ])->values(),
        ]);
    }

    /**
     * DELETE /v1/surveys/{slug}/my-data?ref=xxx — GDPR self-service erasure.
     *
     * Anonymizes PII (respondent_ref, respondent_ip) and soft-deletes all responses
     * for the given ref. Answers and results are deleted immediately.
     * Soft-deleted response rows are hard-purged after 30 days by PurgeDeletedResponsesJob.
     */
    public function eraseMyData(string $slug, Request $request): JsonResponse
    {
        $survey = Survey::bySlug($slug)->firstOrFail();

        $ref = $request->query('ref');
        if (empty($ref)) {
            return response()->json(['error' => 'Tham số ?ref= là bắt buộc.'], 422);
        }

        $responses = SurveyResponse::forSurvey($survey->id)
            ->where('respondent_ref', $ref)
            ->get(['id']);

        if ($responses->isEmpty()) {
            return response()->json(['erased' => 0]);
        }

        $ids = $responses->pluck('id')->all();

        DB::transaction(function () use ($ids) {
            // Delete linked rows immediately
            DB::table('survey_answers')->whereIn('response_id', $ids)->delete();
            DB::table('survey_results')->whereIn('response_id', $ids)->delete();

            // Anonymize PII then soft-delete
            SurveyResponse::whereIn('id', $ids)->update([
                'respondent_ref' => null,
                'respondent_ip'  => null,
            ]);
            SurveyResponse::whereIn('id', $ids)->delete();
        });

        return response()->json(['erased' => count($ids)]);
    }

    /**
     * POST /v1/surveys/{slug}/draft — lưu nháp server-side (cross-device backup).
     * Body: { answers, current_section, respondent_ref? }
     */
    public function saveDraft(string $slug, Request $request): JsonResponse
    {
        $survey = Survey::active()->bySlug($slug)->firstOrFail();

        $data = $request->validate([
            'answers'         => 'required|array',
            'current_section' => 'integer|min:0',
            'respondent_ref'  => 'nullable|string|max:255',
        ]);

        SurveyDraft::updateOrCreate(
            [
                'survey_id'      => $survey->id,
                'respondent_ref' => $data['respondent_ref'] ?? null,
            ],
            [
                'answers'         => $data['answers'],
                'current_section' => $data['current_section'] ?? 0,
                'expires_at'      => now()->addDays(7),
            ]
        );

        return response()->json(['saved' => true]);
    }

    /**
     * GET /v1/surveys/{slug}/draft?ref=xxx — lấy nháp đã lưu.
     */
    public function getDraft(string $slug, Request $request): JsonResponse
    {
        $survey = Survey::active()->bySlug($slug)->firstOrFail();

        $draft = SurveyDraft::where('survey_id', $survey->id)
            ->where('respondent_ref', $request->query('ref'))
            ->first();

        if (! $draft || $draft->isExpired()) {
            return response()->json(null);
        }

        return response()->json([
            'answers'         => $draft->answers,
            'current_section' => $draft->current_section,
            'saved_at'        => $draft->updated_at?->toISOString(),
        ]);
    }
}

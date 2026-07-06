<?php

namespace Modules\Assessment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Assessment\Actions\ForceRerunAssessmentAction;
use Modules\Assessment\Models\Assessment;
use Modules\Assessment\Models\AssessmentResult;
use Modules\Assessment\Models\ScoreBand;
use Modules\Assessment\Models\ScoringFeedback;
use Modules\Assessment\Queries\ListAssessmentResultsHandler;
use Modules\Assessment\Queries\ListAssessmentResultsQuery;

class AssessmentResultController extends Controller
{
    public function index(Assessment $assessment, ListAssessmentResultsHandler $handler): View
    {
        $code = $assessment->assessment_code;
        $this->authorize('assessment.results');
        $results = $handler->handle(new ListAssessmentResultsQuery(
            assessmentCode: $code,
            page:     (int) request('page', 1),
            perPage:  25,
            bandCode: request('band'),
            sortField: request('sort', 'calculated_at'),
            sortDir:   request('dir', 'desc'),
        ));
        return view('assessment::results.index', compact('assessment', 'results'));
    }

    public function show(Assessment $assessment, int $result): View
    {
        $code = $assessment->assessment_code;
        $this->authorize('assessment.results');
        $assessmentResult = AssessmentResult::where('assessment_code', $code)->findOrFail($result);
        $assessmentResult->load([
            'domainScores', 'signalFlags', 'painPoints',
            'recommendations', 'roadmapPhases.phase.milestones',
            'classification', 'questionScores', 'feedback',
        ]);
        $feedback = $assessmentResult->feedback;
        $bands = ScoreBand::forAssessment($code)->ordered()->get()
            ->map(fn($b) => ['code' => $b->band_code, 'label' => $b->label]);
        return view('assessment::results.show', compact('assessment', 'assessmentResult', 'feedback', 'bands'));
    }

    public function recalculate(
        Assessment $assessment, int $result,
        ForceRerunAssessmentAction $action,
    ): JsonResponse {
        $code = $assessment->assessment_code;
        $this->authorize('assessment.reprocess');
        $assessmentResult = AssessmentResult::where('assessment_code', $code)->findOrFail($result);
        $subjectModel = $assessmentResult->subject_type::find($assessmentResult->subject_id);
        if (!$subjectModel) {
            return response()->json(['message' => 'Subject không tồn tại.'], 404);
        }
        try {
            $action->handle($subjectModel);
            return response()->json(['ok' => true, 'message' => 'Đã tính lại điểm thành công.']);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function feedback(Request $request, Assessment $assessment, int $result): JsonResponse
    {
        $code = $assessment->assessment_code;
        $this->authorize('assessment.results');
        $assessmentResult = AssessmentResult::where('assessment_code', $code)->findOrFail($result);
        $data = $request->validate([
            'actual_band' => 'nullable|string|max:64',
            'notes'       => 'nullable|string|max:1000',
        ]);
        ScoringFeedback::updateOrCreate(
            ['result_id' => $assessmentResult->id],
            array_merge($data, ['submitted_by' => auth()->id(), 'submitted_at' => now()])
        );
        return response()->json(['success' => true, 'message' => 'Đã lưu nhận xét.']);
    }
}

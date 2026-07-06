<?php

namespace Modules\Recruitment\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Recruitment\Actions\Backend\SubmitEvaluationAction;
use Modules\Recruitment\Enums\EvaluationVerdict;
use Modules\Recruitment\Models\RcInterview;

class EvaluationController extends Controller
{
    public function create(RcInterview $interview): View
    {
        $this->authorize('view', $interview->application);

        $interview->load(['application.candidate', 'stage', 'panelists.user']);

        // Kiểm tra user có trong panel không (hoặc là HR Admin)
        $isPanelist = $interview->panelists->contains('user_id', auth()->id());
        abort_unless($isPanelist || auth()->user()->hasRole('HR_Admin'), 403, 'Bạn không trong panel phỏng vấn này');

        // Load đánh giá cũ nếu có (draft)
        $existing = $interview->evaluations()->where('evaluator_id', auth()->id())->with('criteria')->first();

        $verdicts = collect(EvaluationVerdict::cases())->map(fn ($v) => ['value' => $v->value, 'text' => $v->label()])->all();

        return view('recruitment::evaluations.create', compact('interview', 'existing', 'verdicts'));
    }

    public function store(Request $request, RcInterview $interview, SubmitEvaluationAction $action): JsonResponse
    {
        $this->authorize('view', $interview->application);

        $isPanelist = $interview->panelists()->where('user_id', auth()->id())->exists();
        abort_unless($isPanelist || auth()->user()->hasRole('HR_Admin'), 403);

        $validated = $request->validate([
            'overall_score'  => ['required', 'integer', 'min:1', 'max:10'],
            'verdict'        => ['required', 'string', 'in:' . implode(',', array_column(EvaluationVerdict::cases(), 'value'))],
            'strengths'      => ['nullable', 'string'],
            'weaknesses'     => ['nullable', 'string'],
            'recommendation' => ['nullable', 'string'],
            'criteria'       => ['nullable', 'array'],
            'criteria.*.criterion_name' => ['required', 'string', 'max:100'],
            'criteria.*.score'          => ['required', 'integer', 'min:1', 'max:10'],
            'criteria.*.comment'        => ['nullable', 'string'],
        ]);

        $evaluation = $action->handle($interview, $validated);

        return response()->json([
            'message'        => 'Đã nộp đánh giá thành công',
            'evaluation_id'  => $evaluation->id,
        ]);
    }

    public function summary(RcInterview $interview): JsonResponse
    {
        $this->authorize('view', $interview->application);

        $interview->load(['evaluations.evaluator', 'evaluations.criteria']);

        $submitted = $interview->evaluations->where('is_submitted', true);

        $verdictCounts = $submitted->groupBy(fn ($e) => $e->verdict?->value)
            ->map->count();

        return response()->json([
            'total_evaluations' => $submitted->count(),
            'avg_score'         => $submitted->count() > 0 ? round($submitted->avg('overall_score'), 1) : null,
            'verdict_counts'    => $verdictCounts,
            'evaluations'       => $submitted->map(fn ($e) => [
                'evaluator'  => $e->evaluator?->name,
                'score'      => $e->overall_score,
                'verdict'    => $e->verdict?->value,
                'verdict_label' => $e->verdict?->label(),
                'strengths'  => $e->strengths,
                'weaknesses' => $e->weaknesses,
                'criteria'   => $e->criteria->map(fn ($c) => [
                    'name'    => $c->criterion_name,
                    'score'   => $c->score,
                    'comment' => $c->comment,
                ]),
                'submitted_at' => $e->submitted_at?->format('d/m/Y H:i'),
            ]),
        ]);
    }
}

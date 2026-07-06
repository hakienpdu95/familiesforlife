<?php

namespace Modules\OcopRubric\Features\ScoringSession\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\OcopRubric\Enums\ScoringSessionMode;
use Modules\OcopRubric\Enums\ScoringSessionStatus;
use Modules\OcopRubric\Features\ProductRegistry\Actions\RegisterProductAction;
use Modules\OcopRubric\Features\ProductRegistry\Data\ProductData;
use Modules\OcopRubric\Features\ScoringSession\Actions\AbandonSessionAction;
use Modules\OcopRubric\Features\ScoringSession\Actions\AnswerCriterionAction;
use Modules\OcopRubric\Features\ScoringSession\Actions\CompleteScoringSessionAction;
use Modules\OcopRubric\Features\ScoringSession\Actions\DuplicateScoringSessionAction;
use Modules\OcopRubric\Features\ScoringSession\Actions\FlagDisqualifierAction;
use Modules\OcopRubric\Features\ScoringSession\Actions\SkipCriterionAction;
use Modules\OcopRubric\Features\ScoringSession\Actions\StartScoringSessionAction;
use Modules\OcopRubric\Features\ScoringSession\Data\AnswerCriterionData;
use Modules\OcopRubric\Features\ScoringSession\Queries\GetNextCriterionHandler;
use Modules\OcopRubric\Features\ScoringSession\Queries\GetNextCriterionQuery;
use Modules\OcopRubric\Features\ScoringSession\Queries\GetQuickWinsHandler;
use Modules\OcopRubric\Features\ScoringSession\Queries\GetQuickWinsQuery;
use Modules\OcopRubric\Features\ScoringSession\Queries\GetSessionProgressHandler;
use Modules\OcopRubric\Features\ScoringSession\Queries\GetSessionProgressQuery;
use Modules\OcopRubric\Models\OcopProduct;
use Modules\OcopRubric\Models\OcopRubricCriterion;
use Modules\OcopRubric\Models\OcopScoringSession;

class ScoringSessionController extends Controller
{
    public function store(Request $request, StartScoringSessionAction $action): RedirectResponse
    {
        $product = OcopProduct::findOrFail($request->integer('product_id'));

        try {
            $session = $action->handle($product, ScoringSessionMode::Practice->value, auth()->id());
        } catch (\DomainException $e) {
            return back()->withErrors(['product_id' => $e->getMessage()]);
        }

        return redirect()->route('ocop.practice.deck', $session);
    }

    public function startSelfAssessment(OcopProduct $product, StartScoringSessionAction $action): RedirectResponse
    {
        try {
            $session = $action->handle($product, ScoringSessionMode::SelfAssessment->value, auth()->id());
        } catch (\DomainException $e) {
            return back()->withErrors(['product' => $e->getMessage()]);
        }

        return redirect()->route('ocop.practice.deck', $session);
    }

    public function deck(OcopScoringSession $session, GetNextCriterionHandler $nextHandler, GetSessionProgressHandler $progressHandler): View|RedirectResponse
    {
        $this->authorize('view', $session);

        if ($session->status !== ScoringSessionStatus::InProgress->value) {
            return redirect()->route('ocop.practice.summary', $session);
        }

        $criterion = $nextHandler->handle(new GetNextCriterionQuery($session->id));
        $progress = $progressHandler->handle(new GetSessionProgressQuery($session->id));
        $disqualifiers = $session->rubricVersion->disqualifiers;
        $flags = $session->disqualifierFlags()->pluck('is_flagged', 'disqualifier_id');

        // Tính sẵn mảng thuần cho @json() trong Blade — tránh nhồi biểu thức
        // lồng nhau (array + closure) trực tiếp vào @json(...), Blade compiler
        // không parse ổn định kiểu đó (lỗi "Unclosed '[' does not match ')'").
        $criterionJson = $criterion ? $this->criterionToArray($criterion) : null;

        return view('ocoprubric::practice.deck', compact('session', 'criterion', 'criterionJson', 'progress', 'disqualifiers', 'flags'));
    }

    public function answer(Request $request, OcopScoringSession $session, AnswerCriterionAction $action, GetNextCriterionHandler $nextHandler, GetSessionProgressHandler $progressHandler): JsonResponse
    {
        $this->authorize('answer', $session);

        $data = AnswerCriterionData::from($request->validate([
            'criterion_id'  => 'required|integer|exists:ocop_rubric_criteria,id',
            'option_id'     => 'nullable|integer|exists:ocop_rubric_options,id',
            'evidence_note' => 'nullable|string',
        ]));

        try {
            $session = $action->handle($session, $data);
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return $this->progressResponse($session, $nextHandler, $progressHandler);
    }

    public function skip(Request $request, OcopScoringSession $session, SkipCriterionAction $action, GetNextCriterionHandler $nextHandler, GetSessionProgressHandler $progressHandler): JsonResponse
    {
        $this->authorize('answer', $session);

        $criterionId = $request->integer('criterion_id');

        try {
            $session = $action->handle($session, $criterionId);
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return $this->progressResponse($session, $nextHandler, $progressHandler);
    }

    public function flagDisqualifier(Request $request, OcopScoringSession $session, FlagDisqualifierAction $action): JsonResponse
    {
        $this->authorize('answer', $session);

        $flag = $action->handle($session, $request->integer('disqualifier_id'), $request->boolean('is_flagged'));

        return response()->json(['is_flagged' => $flag->is_flagged]);
    }

    public function complete(OcopScoringSession $session, CompleteScoringSessionAction $action): RedirectResponse
    {
        $this->authorize('answer', $session);

        try {
            $action->handle($session);
        } catch (\DomainException $e) {
            return back()->withErrors(['session' => $e->getMessage()]);
        }

        return redirect()->route('ocop.practice.summary', $session);
    }

    public function abandon(OcopScoringSession $session, AbandonSessionAction $action): RedirectResponse
    {
        $this->authorize('answer', $session);

        try {
            $action->handle($session);
        } catch (\DomainException $e) {
            return back()->withErrors(['session' => $e->getMessage()]);
        }

        return redirect()->route('ocop.products.show', $session->ocop_product_id)
            ->with('success', 'Đã bỏ dở phiên chấm điểm.');
    }

    public function summary(OcopScoringSession $session, GetQuickWinsHandler $quickWinsHandler): View|RedirectResponse
    {
        $this->authorize('view', $session);

        if ($session->status === ScoringSessionStatus::InProgress->value) {
            return redirect()->route('ocop.practice.deck', $session);
        }

        $quickWins = $quickWinsHandler->handle(new GetQuickWinsQuery($session->id));

        $otherProducts = OcopProduct::where('id', '!=', $session->ocop_product_id)
            ->where('status', '!=', 'archived')
            ->orderBy('name')
            ->get();

        return view('ocoprubric::practice.summary', compact('session', 'quickWins', 'otherProducts'));
    }

    public function duplicate(Request $request, OcopScoringSession $session, DuplicateScoringSessionAction $action): RedirectResponse
    {
        $this->authorize('duplicate', $session);

        $targetProduct = $request->filled('target_product_id')
            ? OcopProduct::findOrFail($request->integer('target_product_id'))
            : RegisterProductAction::run(ProductData::from([
                'product_group_id' => $request->integer('product_group_id') ?: $session->rubricVersion->product_group_id,
                'name'             => $request->string('new_product_name')->value(),
            ]));

        try {
            $newSession = $action->handle($session, $targetProduct, $request->string('mode', $session->mode)->value());
        } catch (\DomainException $e) {
            return back()->withErrors(['session' => $e->getMessage()]);
        }

        // KHÔNG dùng wasRecentlyCreated (luôn true ở cả 3 nhánh) — phân biệt bằng
        // answers đã copy được bao nhiêu và bao nhiêu cần review.
        $needsReview = $newSession->answers->where('needs_review', true)->count();
        $carried = $newSession->answers->count();

        $message = match (true) {
            $carried === 0 => 'Khác bộ tiêu chí — phiên mới trống, cần chấm lại từ đầu.',
            $needsReview > 0 => "Đã nhân bản {$carried} tiêu chí, trong đó {$needsReview} tiêu chí cần xác nhận lại.",
            default => 'Đã nhân bản toàn bộ câu trả lời — kiểm tra lại các tiêu chí khác biệt rồi hoàn thành.',
        };

        return redirect()->route('ocop.practice.deck', $newSession)->with('success', $message);
    }

    /** @return JsonResponse */
    private function progressResponse(OcopScoringSession $session, GetNextCriterionHandler $nextHandler, GetSessionProgressHandler $progressHandler)
    {
        $next = $nextHandler->handle(new GetNextCriterionQuery($session->id));
        $progress = $progressHandler->handle(new GetSessionProgressQuery($session->id));

        return response()->json([
            'progress' => $progress,
            'next_criterion' => $next ? $this->criterionToArray($next) : null,
            'done' => $next === null,
        ]);
    }

    private function criterionToArray(OcopRubricCriterion $criterion): array
    {
        return [
            'id' => $criterion->id,
            'code' => $criterion->code,
            'label' => $criterion->label,
            'max_score' => (float) $criterion->max_score,
            'requirement_note' => $criterion->requirement_note,
            'options' => $criterion->options->map(fn ($o) => [
                'id' => $o->id,
                'label' => $o->label,
                'points' => (float) $o->points,
            ])->values()->all(),
        ];
    }
}

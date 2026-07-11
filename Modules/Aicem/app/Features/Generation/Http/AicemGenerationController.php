<?php

namespace Modules\Aicem\Features\Generation\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Aicem\Features\Generation\Actions\AcceptSuggestionAction;
use Modules\Aicem\Features\Generation\Actions\RejectSuggestionAction;
use Modules\Aicem\Features\Generation\Actions\StartGenerationRunAction;
use Modules\Aicem\Features\Generation\Exceptions\SuggestionAlreadyDecidedException;
use Modules\Aicem\Features\Generation\Exceptions\SuggestionStaleException;
use Modules\Aicem\Models\AicemGenerationRun;
use Modules\Aicem\Models\AicemSuggestion;
use Modules\Aicem\Models\AicemWorkflow;
use Modules\Aicem\Support\Resolvers\Exceptions\AicemSuggestionApplyException;

class AicemGenerationController extends Controller
{
    public function run(Request $request, StartGenerationRunAction $action): RedirectResponse
    {
        $this->authorize('aicem.run_workflow');

        $validated = $request->validate([
            'subject_type' => ['required', 'string'],
            'subject_id'   => ['required', 'integer'],
            'workflow_id'  => ['required', 'integer'],
        ]);

        $modelClass = config("aicem_subjects.{$validated['subject_type']}.model");
        abort_if(! $modelClass, 404, 'subject_type không hợp lệ.');

        $subject = $modelClass::withoutGlobalScopes()->findOrFail($validated['subject_id']);

        // Lọc + xác nhận workflow CÙNG Organization với subject — không dựa vào TenantContext
        // ambient (super-admin bypass hoàn toàn OrganizationScope). Đây là guard cuối cùng chặn
        // đúng bug thật đã xảy ra: panel hiện nhầm workflow của Organization khác (đã sửa ở
        // ListRunnableWorkflowsHandler), người dùng chọn nhầm → tạo run có workflow_id khác
        // Organization với subject → RunAicemWorkflowJob throw TypeError, kẹt vĩnh viễn ở
        // status=running. Chặn ngay tại đây để không phụ thuộc duy nhất vào UI không hiện nút sai.
        $workflow = AicemWorkflow::withoutTenant()
            ->where('organization_id', $subject->organization_id)
            ->where('subject_type', $validated['subject_type'])
            ->where('is_active', true)
            ->find($validated['workflow_id']);

        abort_if(! $workflow, 422, 'Workflow không hợp lệ hoặc không thuộc cùng tổ chức với bài viết/sản phẩm này.');

        $action->handle($subject, $validated['subject_type'], $workflow, (int) auth()->id());

        return back()->with('success', 'Đang xử lý AI... (thường mất 5-30 giây, tải lại trang sau ít phút để xem đề xuất).');
    }

    public function status(AicemGenerationRun $run): JsonResponse
    {
        $this->authorize('aicem.run_workflow');

        return response()->json([
            'status'        => $run->status->value,
            'error_message' => $run->error_message,
            'suggestions'   => $run->suggestions()->get()->map(fn (AicemSuggestion $s) => [
                'id'             => $s->id,
                'field'          => $s->field,
                'block_id'       => $s->block_id,
                'original_text'  => $s->original_text,
                'suggested_text' => $s->suggested_text,
                'reason'         => $s->reason,
                'status'         => $s->status->value,
            ]),
        ]);
    }

    public function acceptSuggestion(AicemGenerationRun $run, AicemSuggestion $suggestion, AcceptSuggestionAction $action): RedirectResponse
    {
        $this->authorize('aicem.decide_suggestion');
        abort_if($suggestion->generation_run_id !== $run->id, 404);

        try {
            $action->handle($suggestion, (int) auth()->id());
        } catch (SuggestionStaleException|SuggestionAlreadyDecidedException|AicemSuggestionApplyException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã áp dụng đề xuất.');
    }

    public function rejectSuggestion(AicemGenerationRun $run, AicemSuggestion $suggestion, RejectSuggestionAction $action): RedirectResponse
    {
        $this->authorize('aicem.decide_suggestion');
        abort_if($suggestion->generation_run_id !== $run->id, 404);

        try {
            $action->handle($suggestion, (int) auth()->id());
        } catch (SuggestionAlreadyDecidedException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã từ chối đề xuất.');
    }
}

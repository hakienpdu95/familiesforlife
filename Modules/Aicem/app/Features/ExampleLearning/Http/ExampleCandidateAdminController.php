<?php

namespace Modules\Aicem\Features\ExampleLearning\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Aicem\Features\ExampleLearning\Actions\ApproveExampleCandidateAction;
use Modules\Aicem\Features\ExampleLearning\Actions\RejectExampleCandidateAction;
use Modules\Aicem\Features\ExampleLearning\Queries\ListExampleCandidatesHandler;
use Modules\Aicem\Features\ExampleLearning\Queries\ListExampleCandidatesQuery;
use Modules\Aicem\Models\AicemExampleCandidate;

/**
 * Duyệt thủ công candidate example_good tự động đề xuất từ bài viết published (Phase 5, mục 11/15).
 * Cùng quyền với quản lý Knowledge Base (aicem.config_prompt) vì bản chất đây cũng là biên tập
 * knowledge base, chỉ khác nguồn gốc đề xuất (tự động từ bài viết thay vì AI_Operator tự viết).
 */
class ExampleCandidateAdminController extends Controller
{
    public function index(Request $request, ListExampleCandidatesHandler $handler): View
    {
        $this->authorize('aicem.config_prompt');

        $candidates = $handler->handle(new ListExampleCandidatesQuery(
            page:   max(1, $request->integer('page', 1)),
            status: $request->string('status')->value() ?: null,
        ));

        return view('aicem::admin.example-candidates.index', compact('candidates'));
    }

    public function approve(AicemExampleCandidate $candidate, ApproveExampleCandidateAction $action): RedirectResponse
    {
        $this->authorize('aicem.config_prompt');

        try {
            $action->handle($candidate, (int) auth()->id());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Đã duyệt — tạo tri thức \"{$candidate->suggested_title}\".");
    }

    public function reject(AicemExampleCandidate $candidate, RejectExampleCandidateAction $action): RedirectResponse
    {
        $this->authorize('aicem.config_prompt');

        try {
            $action->handle($candidate, (int) auth()->id());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã từ chối đề xuất.');
    }
}

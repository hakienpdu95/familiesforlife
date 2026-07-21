<?php

namespace Modules\ContentBrief\Features\Generation\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\ContentBrief\Features\Generation\Actions\CompleteBriefGenerationAction;
use Modules\ContentBrief\Features\Generation\Actions\FailBriefGenerationAction;
use Modules\ContentBrief\Features\Generation\Actions\RequestBriefGenerationAction;
use Modules\ContentBrief\Features\Generation\Actions\StartBriefGenerationAction;
use Modules\ContentBrief\Features\Generation\Data\GenerationOutputData;
use Modules\ContentBrief\Models\ContentBrief;
use Modules\ContentBrief\Models\ContentBriefGeneration;

class BriefGenerationController extends Controller
{
    public function request(ContentBrief $brief, RequestBriefGenerationAction $action): RedirectResponse
    {
        $this->authorize('update', $brief);

        $action->handle($brief);

        return back()->with('success', 'Đã yêu cầu sinh nội dung — trạng thái: Đang chờ.');
    }

    public function start(ContentBriefGeneration $generation, StartBriefGenerationAction $action): RedirectResponse
    {
        $this->authorize('update', $generation->version->contentBrief);

        $action->handle($generation);

        return back()->with('success', 'Đã chuyển sang Đang xử lý.');
    }

    /**
     * spec/ContentBrief_Technical_Specification.md §6.0.1 — hoàn tất thủ công qua dán 1 khối
     * JSON (đúng cách như 1 hệ thống bên ngoài sẽ gọi — không có đường tắt riêng).
     */
    public function complete(Request $request, ContentBriefGeneration $generation, CompleteBriefGenerationAction $action): RedirectResponse
    {
        $this->authorize('update', $generation->version->contentBrief);

        $request->validate(['output_json' => ['required', 'string']]);

        $decoded = json_decode($request->input('output_json'), true);

        throw_if(json_last_error() !== JSON_ERROR_NONE, ValidationException::withMessages([
            'output_json' => 'JSON không hợp lệ: ' . json_last_error_msg(),
        ]));

        $validated = validator($decoded ?? [], [
            'title'                    => ['required', 'string', 'max:300'],
            'meta_description'         => ['nullable', 'string'],
            'sections'                 => ['array'],
            'sections.*.heading'       => ['required', 'string'],
            'sections.*.level'         => ['nullable', 'integer'],
            'sections.*.content_html'  => ['required', 'string'],
            'word_count'               => ['nullable', 'integer'],
            'seo_keywords_used'        => ['array'],
        ])->validate();

        $output = GenerationOutputData::from($validated);

        $action->handle($generation, $output);

        return back()->with('success', 'Đã ghi nhận output — generation hoàn tất.');
    }

    public function fail(Request $request, ContentBriefGeneration $generation, FailBriefGenerationAction $action): RedirectResponse
    {
        $this->authorize('update', $generation->version->contentBrief);

        $validated = $request->validate(['error_message' => ['required', 'string', 'max:500']]);

        $action->handle($generation, $validated['error_message']);

        return back()->with('success', 'Đã ghi nhận lỗi.');
    }
}

<?php

namespace Modules\ContentBrief\Features\BriefManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\ContentBrief\Enums\SearchIntent;
use Modules\ContentBrief\Features\BriefManagement\Actions\ApproveBriefVersionAction;
use Modules\ContentBrief\Features\BriefManagement\Actions\ArchiveBriefAction;
use Modules\ContentBrief\Features\BriefManagement\Actions\CreateBriefAction;
use Modules\ContentBrief\Features\BriefManagement\Actions\DeleteBriefAction;
use Modules\ContentBrief\Features\BriefManagement\Actions\RejectBriefVersionAction;
use Modules\ContentBrief\Features\BriefManagement\Actions\RestoreBriefVersionAction;
use Modules\ContentBrief\Features\BriefManagement\Actions\SubmitBriefForReviewAction;
use Modules\ContentBrief\Features\BriefManagement\Actions\UpdateBriefContentAction;
use Modules\ContentBrief\Features\BriefManagement\Data\BriefSnapshotData;
use Modules\ContentBrief\Features\BriefManagement\Queries\GetBriefVersionHistoryHandler;
use Modules\ContentBrief\Features\BriefManagement\Queries\GetBriefVersionHistoryQuery;
use Modules\ContentBrief\Features\BriefManagement\Queries\ListBriefsForAdminHandler;
use Modules\ContentBrief\Features\BriefManagement\Queries\ListBriefsForAdminQuery;
use Modules\ContentBrief\Models\ContentBrief;
use Modules\ContentBrief\Models\ContentBriefVersion;

class BriefAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ContentBrief::class, 'brief');
    }

    public function index(Request $request, ListBriefsForAdminHandler $handler): View
    {
        $briefs = $handler->handle(new ListBriefsForAdminQuery(
            search: $request->string('q')->trim()->value() ?: null,
            status: $request->string('status')->value() ?: null,
        ));

        return view('contentbrief::admin.content-briefs.index', compact('briefs'));
    }

    public function create(): View
    {
        return view('contentbrief::admin.content-briefs.create');
    }

    public function store(Request $request, CreateBriefAction $action): RedirectResponse
    {
        [$briefAttrs, $snapshot] = $this->extractInput($request);

        $brief = $action->handle($briefAttrs, $snapshot);

        return redirect()->route('backend.content_brief.items.edit', $brief)
            ->with('success', "Brief \"{$brief->title}\" đã được tạo (Nháp).");
    }

    public function edit(ContentBrief $brief): View
    {
        $brief->load('currentVersion', 'assignee');

        return view('contentbrief::admin.content-briefs.edit', compact('brief'));
    }

    public function update(Request $request, ContentBrief $brief, UpdateBriefContentAction $action): RedirectResponse
    {
        [$briefAttrs, $snapshot] = $this->extractInput($request);

        // title/assigned_to là field định danh cấp brief, KHÔNG thuộc snapshot — cập nhật trực tiếp.
        $brief->update([
            'title'       => $briefAttrs['title'],
            'assigned_to' => $briefAttrs['assigned_to'] ?? null,
            'updated_by'  => auth()->id(),
        ]);

        $action->handle($brief, $snapshot);

        return redirect()->route('backend.content_brief.items.edit', $brief)
            ->with('success', 'Cập nhật brief thành công.');
    }

    public function destroy(ContentBrief $brief, DeleteBriefAction $action): RedirectResponse
    {
        $action->handle($brief);

        return redirect()->route('backend.content_brief.items.index')
            ->with('success', "Đã xoá brief \"{$brief->title}\".");
    }

    public function versions(ContentBrief $brief, GetBriefVersionHistoryHandler $handler): View
    {
        $this->authorize('view', $brief);

        $versions = $handler->handle(new GetBriefVersionHistoryQuery($brief->id));

        return view('contentbrief::admin.content-briefs.versions', compact('brief', 'versions'));
    }

    public function submit(ContentBrief $brief, SubmitBriefForReviewAction $action): RedirectResponse
    {
        $this->authorize('update', $brief);

        $action->handle($brief);

        return back()->with('success', 'Đã gửi duyệt.');
    }

    public function approve(ContentBrief $brief, ApproveBriefVersionAction $action): RedirectResponse
    {
        $this->authorize('approve', $brief);

        $action->handle($brief);

        return back()->with('success', 'Đã duyệt version hiện tại.');
    }

    public function reject(Request $request, ContentBrief $brief, RejectBriefVersionAction $action): RedirectResponse
    {
        $this->authorize('approve', $brief);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $action->handle($brief, $validated['reason']);

        return back()->with('success', 'Đã từ chối — đã tạo bản nháp mới để sửa lại.');
    }

    public function restore(ContentBrief $brief, ContentBriefVersion $version, RestoreBriefVersionAction $action): RedirectResponse
    {
        $this->authorize('update', $brief);

        $action->handle($brief, $version);

        return redirect()->route('backend.content_brief.items.edit', $brief)
            ->with('success', 'Đã tạo bản nháp mới từ version đã chọn.');
    }

    public function archive(ContentBrief $brief, ArchiveBriefAction $action): RedirectResponse
    {
        $this->authorize('update', $brief);

        $action->handle($brief);

        return redirect()->route('backend.content_brief.items.index')
            ->with('success', 'Đã lưu trữ brief.');
    }

    /** @return array{0: array{title: string, assigned_to: int|null}, 1: BriefSnapshotData} */
    private function extractInput(Request $request): array
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:200'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],

            'target_keyword'        => ['required', 'string', 'max:150'],
            'secondary_keywords_raw' => ['nullable', 'string'],
            'suggested_category'    => ['nullable', 'string', 'max:100'],
            'search_intent'       => ['required', Rule::in(array_column(SearchIntent::cases(), 'value'))],
            'audience_persona'    => ['nullable', 'string'],
            'tone_of_voice'       => ['nullable', 'string'],
            'word_count_min'      => ['nullable', 'integer', 'min:0'],
            'word_count_max'      => ['nullable', 'integer', 'min:0', 'gte:word_count_min'],

            'outline'              => ['array'],
            'outline.*.heading'    => ['required', 'string', 'max:200'],
            'outline.*.level'      => ['nullable', 'integer', 'min:1', 'max:6'],
            'outline.*.notes'      => ['nullable', 'string'],

            'key_facts'             => ['array'],
            'key_facts.*.fact'      => ['required', 'string'],
            'key_facts.*.source_url' => ['nullable', 'string'],

            'competitor_references'          => ['array'],
            'competitor_references.*.url'    => ['required', 'string'],
            'competitor_references.*.notes'  => ['nullable', 'string'],

            'related_references'         => ['array'],
            'related_references.*.type'  => ['required', 'string'],
            'related_references.*.id'    => ['required'],
            'related_references.*.label' => ['nullable', 'string'],

            'internal_linking_notes'     => ['nullable', 'string'],
            'seo_title_suggestion'       => ['nullable', 'string'],
            'seo_description_suggestion' => ['nullable', 'string'],
            'additional_instructions'    => ['nullable', 'string'],
        ]);

        $briefAttrs = [
            'title'       => $validated['title'],
            'assigned_to' => $validated['assigned_to'] ?? null,
        ];

        $secondaryKeywords = collect(explode(',', $validated['secondary_keywords_raw'] ?? ''))
            ->map(fn ($k) => trim($k))
            ->filter()
            ->values()
            ->all();

        $snapshotInput = Arr::except($validated, ['title', 'assigned_to', 'secondary_keywords_raw']);
        $snapshotInput['secondary_keywords'] = $secondaryKeywords;

        $snapshot = BriefSnapshotData::from($snapshotInput);

        return [$briefAttrs, $snapshot];
    }
}

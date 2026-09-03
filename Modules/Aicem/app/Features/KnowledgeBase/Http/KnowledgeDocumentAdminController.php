<?php

namespace Modules\Aicem\Features\KnowledgeBase\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\Aicem\Features\KnowledgeBase\Actions\CreateKnowledgeDocumentAction;
use Modules\Aicem\Features\KnowledgeBase\Actions\DeleteKnowledgeDocumentAction;
use Modules\Aicem\Features\KnowledgeBase\Actions\RestoreKnowledgeDocumentVersionAction;
use Modules\Aicem\Features\KnowledgeBase\Actions\UpdateKnowledgeDocumentAction;
use Modules\Aicem\Features\KnowledgeBase\Data\KnowledgeDocumentData;
use Modules\Aicem\Features\KnowledgeBase\Exceptions\InvalidKnowledgeDocumentException;
use Modules\Aicem\Models\AicemKnowledgeDocument;
use Modules\Aicem\Models\AicemKnowledgeDocumentVersion;

class KnowledgeDocumentAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(AicemKnowledgeDocument::class, 'document');
    }

    /** Dữ liệu bảng lấy qua KnowledgeDocumentApiController (Tabulator, remote pagination/sort/filter). */
    public function index(): View
    {
        return view('aicem::admin.knowledge-base.index', [
            'subjectTypes'   => $this->subjectTypeOptions(),
            'staleAfterDays' => (int) config('aicem.freshness.stale_after_days', 90),
        ]);
    }

    public function create(): View
    {
        return view('aicem::admin.knowledge-base.create', [
            'slotDefinitions'  => config('aicem_subjects.knowledge_slot_definitions'),
            'subjectTypes'     => $this->subjectTypeOptions(),
            'taxonomySchema'   => $this->taxonomySchema(),
        ]);
    }

    public function store(Request $request, CreateKnowledgeDocumentAction $action): RedirectResponse
    {
        $data = KnowledgeDocumentData::from($this->validated($request));

        try {
            $document = $action->handle($data);
        } catch (InvalidKnowledgeDocumentException $e) {
            return back()->withInput()->withErrors(['type' => $e->getMessage()]);
        }

        return redirect()->route('backend.aicem.knowledge-documents.edit', $document)
            ->with('success', "Đã tạo tri thức \"{$document->title}\".");
    }

    public function edit(AicemKnowledgeDocument $document): View
    {
        $document->load(['versions' => fn ($q) => $q->orderByDesc('version')->with('changer:id,name')]);

        return view('aicem::admin.knowledge-base.edit', [
            'document'       => $document,
            'taxonomySchema' => $this->taxonomySchema(),
        ]);
    }

    public function update(Request $request, AicemKnowledgeDocument $document, UpdateKnowledgeDocumentAction $action): RedirectResponse
    {
        $data = KnowledgeDocumentData::from($this->validated($request, $document));

        try {
            $action->handle($document, $data);
        } catch (InvalidKnowledgeDocumentException $e) {
            return back()->withInput()->withErrors(['scope_json' => $e->getMessage()]);
        }

        return redirect()->route('backend.aicem.knowledge-documents.edit', $document)
            ->with('success', 'Cập nhật tri thức thành công.');
    }

    public function destroy(Request $request, AicemKnowledgeDocument $document, DeleteKnowledgeDocumentAction $action): RedirectResponse|JsonResponse
    {
        $title = $document->title;

        $action->handle($document);

        if ($request->expectsJson()) {
            return response()->json(['message' => "Đã xoá tri thức \"{$title}\"."]);
        }

        return redirect()->route('backend.aicem.knowledge-documents.index')
            ->with('success', "Đã xoá tri thức \"{$title}\".");
    }

    public function restoreVersion(
        AicemKnowledgeDocument $document,
        AicemKnowledgeDocumentVersion $version,
        RestoreKnowledgeDocumentVersionAction $action
    ): RedirectResponse {
        $this->authorize('rollback', $document);

        if ($version->knowledge_document_id !== $document->id) {
            abort(404);
        }

        $action->handle($document, $version);

        return redirect()->route('backend.aicem.knowledge-documents.edit', $document)
            ->with('success', "Đã khôi phục về phiên bản #{$version->version}.");
    }

    /**
     * Map subject_type => taxonomy_keys hợp lệ — dùng để hint ở ô "Scope (JSON)" tự đổi theo
     * subject_type đang chọn (Modules/Aicem/config/aicem_subjects.php::taxonomy_keys là nguồn
     * sự thật duy nhất, KHÔNG lặp lại danh sách key tĩnh trong Blade — trước đây hint luôn ghi
     * cứng ví dụ của post_article dù chọn subject_type nào, sai với product).
     *
     * @return array<string, array<int, string>>
     */
    private function taxonomySchema(): array
    {
        return collect(config('aicem_subjects'))
            ->except(['knowledge_slot_definitions', 'knowledge_tier_labels'])
            ->map(fn (array $cfg): array => $cfg['taxonomy_keys'] ?? [])
            ->all();
    }

    /** @return array<int, array{key: string, label: string}> */
    private function subjectTypeOptions(): array
    {
        return collect(config('aicem_subjects'))
            ->except(['knowledge_slot_definitions', 'knowledge_tier_labels'])
            ->map(fn ($cfg, $key) => ['key' => $key, 'label' => $cfg['label']])
            ->values()
            ->all();
    }

    private function validated(Request $request, ?AicemKnowledgeDocument $document = null): array
    {
        $subjectTypeKeys = collect(config('aicem_subjects'))->except(['knowledge_slot_definitions', 'knowledge_tier_labels'])->keys()->all();

        $rules = [
            'title'       => ['required', 'string', 'max:255'],
            'content'     => ['required', 'string'],
            'scope_match' => ['required', Rule::in(['any', 'all'])],
            'priority'    => ['nullable', 'integer', 'min:1', 'max:999'],
            'scope_json'  => ['nullable', 'string'],
        ];

        // type/subject_type chỉ nhận lúc tạo mới — bất biến sau đó (mục "Nguyên tắc chung" mục 11.1).
        if (! $document) {
            $rules['type']         = ['required', 'string', 'max:50'];
            $rules['subject_type'] = ['nullable', Rule::in($subjectTypeKeys)];
        }

        $validated = $request->validate($rules);

        $scope = null;
        if (! empty($validated['scope_json'])) {
            $decoded = json_decode($validated['scope_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
                throw ValidationException::withMessages([
                    'scope_json' => 'Scope phải là JSON object hợp lệ, VD: {"category_slugs": ["an-toan-giac-ngu"]}',
                ]);
            }
            $scope = $decoded;
        }
        $validated['scope'] = $scope;
        unset($validated['scope_json']);

        if ($document) {
            $validated['type']         = $document->type;
            $validated['subject_type'] = $document->subject_type;
        }

        return $validated;
    }
}

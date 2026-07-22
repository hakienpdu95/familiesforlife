<?php

namespace Modules\Aicem\Features\KnowledgeBase\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Aicem\Features\KnowledgeBase\Http\Resources\KnowledgeDocumentListResource;
use Modules\Aicem\Features\KnowledgeBase\Queries\ListKnowledgeDocumentsHandler;
use Modules\Aicem\Features\KnowledgeBase\Queries\ListKnowledgeDocumentsQuery;
use Modules\Aicem\Models\AicemKnowledgeDocument;

/** JSON backend cho Tabulator ở dashboard/aicem/knowledge-documents — cùng pattern ArticleApiController. */
class KnowledgeDocumentApiController extends Controller
{
    public function index(Request $request, ListKnowledgeDocumentsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', AicemKnowledgeDocument::class);

        $typeKeys = array_keys(config('aicem_subjects.knowledge_slot_definitions', []));
        $subjectTypeKeys = collect(config('aicem_subjects'))->except('knowledge_slot_definitions')->keys()->all();

        $validated = $request->validate([
            'page'         => ['nullable', 'integer', 'min:1'],
            'size'         => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'       => ['nullable', 'string', 'max:200'],
            'type'         => ['nullable', 'string', Rule::in($typeKeys)],
            'subject_type' => ['nullable', 'string', Rule::in($subjectTypeKeys)],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListKnowledgeDocumentsQuery(
            page:        max(1, (int) ($validated['page'] ?? 1)),
            perPage:     min(100, max(5, (int) ($validated['size'] ?? 25))),
            search:      $validated['search'] ?? null,
            type:        $validated['type'] ?? null,
            subjectType: $validated['subject_type'] ?? null,
            sortField:   $sortField,
            sortDir:     $sortDir,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => KnowledgeDocumentListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}

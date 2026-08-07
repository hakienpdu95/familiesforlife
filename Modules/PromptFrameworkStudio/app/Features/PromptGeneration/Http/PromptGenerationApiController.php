<?php

namespace Modules\PromptFrameworkStudio\Features\PromptGeneration\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\PromptFrameworkStudio\Features\PromptGeneration\Http\Resources\GeneratedPromptListResource;
use Modules\PromptFrameworkStudio\Features\PromptGeneration\Queries\ListGeneratedPromptsForAdminHandler;
use Modules\PromptFrameworkStudio\Features\PromptGeneration\Queries\ListGeneratedPromptsForAdminQuery;

/** JSON backend cho Tabulator ở dashboard/prompt-studio/prompts — cùng pattern ContentOutlineApiController. */
class PromptGenerationApiController extends Controller
{
    public function index(Request $request, ListGeneratedPromptsForAdminHandler $handler): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:150'],
            'framework_key' => ['nullable', 'string', 'max:30'],
        ]);

        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'updated_at') : 'updated_at';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListGeneratedPromptsForAdminQuery(
            search: $validated['search'] ?? null,
            frameworkKey: $validated['framework_key'] ?? null,
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 20))),
            sortField: $sortField,
            sortDir: $sortDir,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data' => GeneratedPromptListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }
}

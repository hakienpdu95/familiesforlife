<?php

namespace Modules\ContentOutlines\Features\OutlineGeneration\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ContentOutlines\Features\OutlineGeneration\Http\Resources\ContentOutlineListResource;
use Modules\ContentOutlines\Features\OutlineGeneration\Queries\ListContentOutlinesForAdminHandler;
use Modules\ContentOutlines\Features\OutlineGeneration\Queries\ListContentOutlinesForAdminQuery;
use Modules\Post\Models\PostCategory;

/** JSON backend cho Tabulator ở dashboard/content-outlines — cùng pattern BannerApiController. */
class ContentOutlineApiController extends Controller
{
    public function index(Request $request, ListContentOutlinesForAdminHandler $handler): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:150'],
            'category_uuid' => ['nullable', 'string', 'uuid'],
        ]);

        $categoryId = null;
        if (! empty($validated['category_uuid'])) {
            $categoryId = PostCategory::where('uuid', $validated['category_uuid'])->value('id');
        }

        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListContentOutlinesForAdminQuery(
            search: $validated['search'] ?? null,
            postCategoryId: $categoryId,
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 20))),
            sortField: $sortField,
            sortDir: $sortDir,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data' => ContentOutlineListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }
}

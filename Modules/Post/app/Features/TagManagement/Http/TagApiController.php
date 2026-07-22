<?php

namespace Modules\Post\Features\TagManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Post\Features\TagManagement\Http\Resources\TagListResource;
use Modules\Post\Features\TagManagement\Queries\ListTagsForAdminHandler;
use Modules\Post\Features\TagManagement\Queries\ListTagsForAdminQuery;
use Modules\Post\Models\PostTag;

/** JSON backend cho Tabulator ở dashboard/posts/tags — cùng pattern ArticleApiController. */
class TagApiController extends Controller
{
    public function index(Request $request, ListTagsForAdminHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', PostTag::class);

        $validated = $request->validate([
            'page'   => ['nullable', 'integer', 'min:1'],
            'size'   => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'name') : 'name';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'desc' ? 'desc' : 'asc';

        $query = new ListTagsForAdminQuery(
            search:    $validated['search'] ?? null,
            page:      max(1, (int) ($validated['page'] ?? 1)),
            perPage:   min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField: $sortField,
            sortDir:   $sortDir,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => TagListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}

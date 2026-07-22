<?php

namespace Modules\Page\Features\PageManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Features\PageManagement\Http\Resources\PageListResource;
use Modules\Page\Features\PageManagement\Queries\ListPagesForAdminHandler;
use Modules\Page\Features\PageManagement\Queries\ListPagesForAdminQuery;
use Modules\Page\Models\Page;

/** JSON backend cho Tabulator ở dashboard/pages/items — cùng pattern ArticleApiController. */
class PageApiController extends Controller
{
    public function index(Request $request, ListPagesForAdminHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', Page::class);

        $validated = $request->validate([
            'page'   => ['nullable', 'integer', 'min:1'],
            'size'   => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', 'string', 'in:' . implode(',', array_column(PageStatus::cases(), 'value'))],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'updated_at') : 'updated_at';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListPagesForAdminQuery(
            search:    $validated['search'] ?? null,
            status:    $validated['status'] ?? null,
            page:      max(1, (int) ($validated['page'] ?? 1)),
            perPage:   min(100, max(5, (int) ($validated['size'] ?? 20))),
            sortField: $sortField,
            sortDir:   $sortDir,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => PageListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}

<?php

namespace Modules\Banner\Features\BannerManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Banner\Features\BannerManagement\Http\Resources\BannerListResource;
use Modules\Banner\Features\BannerManagement\Queries\ListBannersForAdminHandler;
use Modules\Banner\Features\BannerManagement\Queries\ListBannersForAdminQuery;
use Modules\Banner\Models\Banner;

/** JSON backend cho Tabulator ở dashboard/banners/items — cùng pattern ArticleApiController. */
class BannerApiController extends Controller
{
    public function index(Request $request, ListBannersForAdminHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', Banner::class);

        $validated = $request->validate([
            'page'        => ['nullable', 'integer', 'min:1'],
            'size'        => ['nullable', 'integer', 'min:5', 'max:100'],
            'placement'   => ['nullable', 'string', Rule::in(Banner::validPlacementKeys())],
            'target_type' => ['nullable', 'string', 'in:global,category,province'],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'sort_order') : 'sort_order';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'desc' ? 'desc' : 'asc';

        $query = new ListBannersForAdminQuery(
            placement:  $validated['placement'] ?? null,
            targetType: $validated['target_type'] ?? null,
            page:       max(1, (int) ($validated['page'] ?? 1)),
            perPage:    min(100, max(5, (int) ($validated['size'] ?? 20))),
            sortField:  $sortField,
            sortDir:    $sortDir,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => BannerListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}

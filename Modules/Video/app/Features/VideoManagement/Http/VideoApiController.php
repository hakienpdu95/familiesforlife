<?php

namespace Modules\Video\Features\VideoManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Video\Features\VideoManagement\Http\Resources\VideoListResource;
use Modules\Video\Features\VideoManagement\Queries\ListVideosForAdminHandler;
use Modules\Video\Features\VideoManagement\Queries\ListVideosForAdminQuery;
use Modules\Video\Models\Video;

/** JSON backend cho Tabulator ở dashboard/videos/items — cùng pattern BannerApiController. */
class VideoApiController extends Controller
{
    public function index(Request $request, ListVideosForAdminHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', Video::class);

        $validated = $request->validate([
            'page'      => ['nullable', 'integer', 'min:1'],
            'size'      => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'    => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'in:0,1'],
        ]);

        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'sort_order') : 'sort_order';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'desc' ? 'desc' : 'asc';

        $query = new ListVideosForAdminQuery(
            search: $validated['search'] ?? null,
            isActive: array_key_exists('is_active', $validated) && $validated['is_active'] !== null
                ? (bool) $validated['is_active']
                : null,
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 20))),
            sortField: $sortField,
            sortDir: $sortDir,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => VideoListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}

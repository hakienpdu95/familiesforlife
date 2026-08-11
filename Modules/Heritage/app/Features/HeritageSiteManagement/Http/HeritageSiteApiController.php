<?php

namespace Modules\Heritage\Features\HeritageSiteManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Heritage\Enums\HeritageSiteStatus;
use Modules\Heritage\Features\HeritageSiteManagement\Http\Resources\HeritageSiteListResource;
use Modules\Heritage\Features\HeritageSiteManagement\Queries\ListHeritageSitesForAdminHandler;
use Modules\Heritage\Features\HeritageSiteManagement\Queries\ListHeritageSitesForAdminQuery;
use Modules\Heritage\Models\HeritageSite;

/** JSON backend cho Tabulator ở dashboard/heritage/sites — cùng pattern OcopProductApiController. */
class HeritageSiteApiController extends Controller
{
    public function index(Request $request, ListHeritageSitesForAdminHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', HeritageSite::class);

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
            'heritage_type' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_column(HeritageSiteStatus::cases(), 'value'))],
        ]);

        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListHeritageSitesForAdminQuery(
            search: $validated['search'] ?? null,
            heritageType: $validated['heritage_type'] ?? null,
            status: $validated['status'] ?? null,
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 20))),
            sortField: $sortField,
            sortDir: $sortDir,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data' => HeritageSiteListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }
}

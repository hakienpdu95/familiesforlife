<?php

namespace Modules\N8n\Features\ConnectionManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\N8n\Features\ConnectionManagement\Http\Resources\N8nConnectionListResource;
use Modules\N8n\Features\ConnectionManagement\Queries\ListN8nConnectionsForAdminHandler;
use Modules\N8n\Features\ConnectionManagement\Queries\ListN8nConnectionsForAdminQuery;

/** JSON backend cho Tabulator ở dashboard/n8n/connections — cùng pattern BannerApiController. */
class N8nConnectionApiController extends Controller
{
    public function index(Request $request, ListN8nConnectionsForAdminHandler $handler): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->isPlatformOps() || $user?->isPlatformViewer() || $user?->hasRole('super-admin'), 403);

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:150'],
            'include_trashed' => ['nullable', 'boolean'],
        ]);

        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListN8nConnectionsForAdminQuery(
            search: $validated['search'] ?? null,
            includeTrashed: (bool) ($validated['include_trashed'] ?? false),
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 20))),
            sortField: $sortField,
            sortDir: $sortDir,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data' => N8nConnectionListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }
}

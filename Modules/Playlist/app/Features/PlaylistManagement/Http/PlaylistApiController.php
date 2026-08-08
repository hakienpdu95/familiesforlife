<?php

namespace Modules\Playlist\Features\PlaylistManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Playlist\Features\PlaylistManagement\Actions\SearchPlaylistableItemsAction;
use Modules\Playlist\Features\PlaylistManagement\Http\Resources\PlaylistListResource;
use Modules\Playlist\Features\PlaylistManagement\Queries\ListPlaylistsForAdminHandler;
use Modules\Playlist\Features\PlaylistManagement\Queries\ListPlaylistsForAdminQuery;
use Modules\Playlist\Models\Playlist;

/** JSON backend cho Tabulator ở dashboard/playlists/items + ô tìm kiếm hợp nhất — cùng pattern VideoApiController. */
class PlaylistApiController extends Controller
{
    public function index(Request $request, ListPlaylistsForAdminHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', Playlist::class);

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'in:0,1'],
        ]);

        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'sort_order') : 'sort_order';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'desc' ? 'desc' : 'asc';

        $query = new ListPlaylistsForAdminQuery(
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
            'data' => PlaylistListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    /** Ô tìm kiếm hợp nhất khi thêm item (§6.4) — trộn kết quả Video + PostArticle kèm badge phân loại. */
    public function searchableItems(Request $request, Playlist $playlist, SearchPlaylistableItemsAction $search): JsonResponse
    {
        $this->authorize('update', $playlist);

        $validated = $request->validate(['q' => ['required', 'string', 'min:2', 'max:255']]);

        return response()->json(['data' => $search->handle($validated['q'], $playlist)]);
    }
}

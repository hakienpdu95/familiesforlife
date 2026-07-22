<?php

namespace Modules\Event\Features\EventModeration\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Event\Enums\EventStatus;
use Modules\Event\Features\EventModeration\Http\Resources\EventListResource;
use Modules\Event\Features\EventModeration\Queries\ListEventsForAdminHandler;
use Modules\Event\Features\EventModeration\Queries\ListEventsForAdminQuery;
use Modules\Event\Models\Event;

/** JSON backend cho Tabulator ở dashboard/events — cùng pattern ArticleApiController. */
class EventApiController extends Controller
{
    public function index(Request $request, ListEventsForAdminHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', Event::class);

        $validated = $request->validate([
            'page'   => ['nullable', 'integer', 'min:1'],
            'size'   => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', 'string', 'in:' . implode(',', array_column(EventStatus::cases(), 'value'))],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListEventsForAdminQuery(
            search:    $validated['search'] ?? null,
            status:    $validated['status'] ?? null,
            page:      max(1, (int) ($validated['page'] ?? 1)),
            perPage:   min(100, max(5, (int) ($validated['size'] ?? 20))),
            sortField: $sortField,
            sortDir:   $sortDir,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => EventListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}

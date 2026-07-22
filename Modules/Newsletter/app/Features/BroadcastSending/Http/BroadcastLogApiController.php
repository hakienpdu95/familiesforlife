<?php

namespace Modules\Newsletter\Features\BroadcastSending\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Newsletter\Features\BroadcastSending\Http\Resources\BroadcastLogListResource;
use Modules\Newsletter\Features\BroadcastSending\Queries\ListBroadcastLogsForAdminHandler;
use Modules\Newsletter\Features\BroadcastSending\Queries\ListBroadcastLogsForAdminQuery;
use Modules\Newsletter\Models\NewsletterSubscriber;

/** JSON backend cho Tabulator ở dashboard/newsletter/broadcast/logs — cùng pattern ArticleApiController. */
class BroadcastLogApiController extends Controller
{
    public function index(Request $request, ListBroadcastLogsForAdminHandler $handler): JsonResponse
    {
        // §11 (BroadcastAdminController::logs()) — dùng cùng ability viewAny(NewsletterSubscriber)
        // như trang gốc, KHÔNG có Policy riêng cho NewsletterBroadcastLog::viewAny.
        $this->authorize('viewAny', NewsletterSubscriber::class);

        $validated = $request->validate([
            'page'   => ['nullable', 'integer', 'min:1'],
            'size'   => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListBroadcastLogsForAdminQuery(
            page:      max(1, (int) ($validated['page'] ?? 1)),
            perPage:   min(100, max(5, (int) ($validated['size'] ?? 25))),
            search:    $validated['search'] ?? null,
            sortField: $sortField,
            sortDir:   $sortDir,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => BroadcastLogListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}

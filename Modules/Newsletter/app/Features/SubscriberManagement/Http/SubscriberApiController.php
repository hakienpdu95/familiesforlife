<?php

namespace Modules\Newsletter\Features\SubscriberManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Newsletter\Enums\SubscriberStatus;
use Modules\Newsletter\Features\SubscriberManagement\Http\Resources\SubscriberListResource;
use Modules\Newsletter\Features\SubscriberManagement\Queries\ListSubscribersForAdminHandler;
use Modules\Newsletter\Features\SubscriberManagement\Queries\ListSubscribersForAdminQuery;
use Modules\Newsletter\Models\NewsletterSubscriber;

/** JSON backend cho Tabulator ở dashboard/newsletter/subscribers — cùng pattern ArticleApiController. */
class SubscriberApiController extends Controller
{
    public function index(Request $request, ListSubscribersForAdminHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', NewsletterSubscriber::class);

        $validated = $request->validate([
            'page'   => ['nullable', 'integer', 'min:1'],
            'size'   => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', 'string', 'in:' . implode(',', array_column(SubscriberStatus::cases(), 'value'))],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'subscribed_at') : 'subscribed_at';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListSubscribersForAdminQuery(
            page:      max(1, (int) ($validated['page'] ?? 1)),
            perPage:   min(100, max(5, (int) ($validated['size'] ?? 25))),
            search:    $validated['search'] ?? null,
            status:    $validated['status'] ?? null,
            sortField: $sortField,
            sortDir:   $sortDir,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => SubscriberListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}

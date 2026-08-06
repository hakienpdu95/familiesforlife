<?php

namespace Modules\N8n\Features\LogViewing\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\N8n\Features\LogViewing\Http\Resources\N8nInboundLogListResource;
use Modules\N8n\Features\LogViewing\Http\Resources\N8nOutboundLogListResource;
use Modules\N8n\Features\LogViewing\Queries\ListN8nInboundLogsHandler;
use Modules\N8n\Features\LogViewing\Queries\ListN8nInboundLogsQuery;
use Modules\N8n\Features\LogViewing\Queries\ListN8nOutboundLogsHandler;
use Modules\N8n\Features\LogViewing\Queries\ListN8nOutboundLogsQuery;

/** JSON backend cho 2 bảng Tabulator (inbound/outbound) ở dashboard/n8n/logs. */
class N8nLogApiController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            abort_unless($user?->isPlatformOps() || $user?->isPlatformViewer() || $user?->hasRole('super-admin'), 403);

            return $next($request);
        });
    }

    public function inbound(Request $request, ListN8nInboundLogsHandler $handler): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:5', 'max:100'],
            'connection_id' => ['nullable', 'integer'],
            'signature_valid' => ['nullable', 'in:0,1'],
        ]);

        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'received_at') : 'received_at';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListN8nInboundLogsQuery(
            connectionId: isset($validated['connection_id']) ? (int) $validated['connection_id'] : null,
            signatureValid: isset($validated['signature_valid']) ? (bool) $validated['signature_valid'] : null,
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 20))),
            sortField: $sortField,
            sortDir: $sortDir,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data' => N8nInboundLogListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function outbound(Request $request, ListN8nOutboundLogsHandler $handler): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:5', 'max:100'],
            'connection_id' => ['nullable', 'integer'],
            'success' => ['nullable', 'in:0,1'],
        ]);

        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'requested_at') : 'requested_at';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListN8nOutboundLogsQuery(
            connectionId: isset($validated['connection_id']) ? (int) $validated['connection_id'] : null,
            success: isset($validated['success']) ? (bool) $validated['success'] : null,
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 20))),
            sortField: $sortField,
            sortDir: $sortDir,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data' => N8nOutboundLogListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }
}

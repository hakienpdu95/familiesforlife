<?php

namespace Modules\N8n\Features\InboundWebhook\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\N8n\Features\InboundWebhook\Actions\HandleInboundN8nCallAction;

/**
 * spec/N8n_Integration_Technical_Specification.md §5.1 — public, server-to-server (n8n),
 * KHÔNG session/CSRF. Controller CHỈ gọi HandleInboundN8nCallAction — toàn bộ logic 11 bước
 * (§5.2) nằm trong Action.
 */
class N8nInboundWebhookController extends Controller
{
    public function handle(Request $request, string $token, HandleInboundN8nCallAction $action): JsonResponse
    {
        return $action->handle($request, $token);
    }
}

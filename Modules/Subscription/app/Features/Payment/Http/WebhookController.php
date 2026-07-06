<?php

namespace Modules\Subscription\Features\Payment\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Subscription\Features\Payment\Actions\HandleWebhookAction;

/**
 * Single entry point for all payment gateway webhooks.
 * Route: POST /billing/webhook/{gateway}
 *
 * CSRF-exempt (configured in bootstrap/app.php).
 * Always returns 200 — gateways treat non-200 as failure and retry.
 * Error signalling is done in the response body per each gateway's format.
 */
class WebhookController extends Controller
{
    public function __construct(
        private readonly HandleWebhookAction $handleWebhook,
    ) {}

    public function handle(string $gateway, Request $request): JsonResponse
    {
        $ack = $this->handleWebhook->handle($gateway, $request);

        // Always return 200 — gateways must not get 4xx/5xx or they'll retry endlessly
        return response()->json($ack, 200);
    }
}

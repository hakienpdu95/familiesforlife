<?php

namespace Modules\Subscription\Features\Payment\Gateways;

use Illuminate\Http\Request;
use Modules\Subscription\Enums\GatewayType;
use Modules\Subscription\Features\Payment\Contracts\PaymentGatewayInterface;
use Modules\Subscription\Models\SubscriptionInvoice;

/**
 * Manual gateway — for admin use and local development.
 * No external calls; admin confirms payment directly.
 */
class ManualGateway implements PaymentGatewayInterface
{
    public function slug(): string      { return 'manual'; }
    public function type(): GatewayType { return GatewayType::Manual; }
    public function isEnabled(): bool
    {
        return app()->environment(['local', 'testing'])
            || config('subscription.gateways.manual.enabled', false);
    }

    public function buildCheckoutUrl(SubscriptionInvoice $invoice, string $returnUrl): ?string
    {
        // Admin uses a dedicated confirm route, not an external URL
        return null;
    }

    public function verifyWebhook(Request $request): bool
    {
        if (app()->environment(['local', 'testing'])) {
            return true;
        }
        $secret = config('subscription.gateways.manual.secret');
        if (!$secret) return false;
        return hash_equals($secret, (string) $request->header('X-Manual-Secret', ''));
    }
    public function verifyReturn(Request $request): ?bool   { return null; }

    public function extractInvoiceNumber(Request $request): string
    {
        return $request->input('invoice_number', '');
    }

    public function extractPaymentRef(Request $request): string
    {
        return 'MANUAL-' . now()->format('YmdHis');
    }

    public function extractAmount(Request $request): float
    {
        return (float) $request->input('amount', 0);
    }

    public function webhookAck(bool $success): array
    {
        return ['success' => $success];
    }
}

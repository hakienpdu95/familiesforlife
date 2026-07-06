<?php

namespace Modules\Subscription\Features\Payment\Gateways;

use Illuminate\Http\Request;
use Modules\Subscription\Enums\GatewayType;
use Modules\Subscription\Features\Payment\Contracts\PaymentGatewayInterface;
use Modules\Subscription\Models\SubscriptionInvoice;

/**
 * SePay gateway — bank transfer monitoring service.
 *
 * SePay watches a bank account for incoming transfers. When it detects
 * a transfer whose description contains the invoice number, it fires a
 * POST webhook. No redirect occurs; users see bank transfer instructions
 * and pay via their banking app.
 *
 * Auth: Authorization header = "Apikey {api_key}"
 * Payload fields used: content (transfer description), transferAmount, referenceCode
 */
class SePayGateway implements PaymentGatewayInterface
{
    public function slug(): string      { return 'sepay'; }
    public function type(): GatewayType { return GatewayType::Monitor; }

    public function isEnabled(): bool
    {
        return filled(config('subscription.gateways.sepay.api_key'))
            && filled(config('subscription.gateways.sepay.account_number'));
    }

    /**
     * SePay doesn't redirect — return null, controller shows bank transfer
     * instructions page with the invoice number as the transfer description.
     */
    public function buildCheckoutUrl(SubscriptionInvoice $invoice, string $returnUrl): ?string
    {
        return null;
    }

    public function verifyWebhook(Request $request): bool
    {
        $authHeader = $request->header('Authorization', '');
        $expected   = 'Apikey ' . config('subscription.gateways.sepay.api_key');

        if (!hash_equals($expected, $authHeader)) {
            return false;
        }

        // Require inbound transfer (not outbound)
        if ($request->input('transferType') !== 'in') {
            return false;
        }

        return true;
    }

    public function verifyReturn(Request $request): ?bool
    {
        // Monitor gateways never redirect users — no return URL
        return null;
    }

    public function extractInvoiceNumber(Request $request): string
    {
        // The transfer description (content) must contain the invoice number.
        // We match the INV-YYYY-ORG-SEQ pattern from the content field.
        $content = $request->input('content', '');
        if (preg_match('/INV-\d{4}-\d+-\d{4}/', $content, $matches)) {
            return $matches[0];
        }
        return '';
    }

    public function extractPaymentRef(Request $request): string
    {
        return $request->input('referenceCode', '');
    }

    public function extractAmount(Request $request): float
    {
        return (float) $request->input('transferAmount', 0);
    }

    public function webhookAck(bool $success): array
    {
        return ['success' => $success, 'message' => $success ? 'Processed' : 'Error'];
    }
}

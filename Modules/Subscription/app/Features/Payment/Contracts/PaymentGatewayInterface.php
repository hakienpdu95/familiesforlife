<?php

namespace Modules\Subscription\Features\Payment\Contracts;

use Illuminate\Http\Request;
use Modules\Subscription\Enums\GatewayType;
use Modules\Subscription\Models\SubscriptionInvoice;

/**
 * Each gateway implements this contract. The WebhookController resolves
 * the correct gateway by slug via PaymentGatewayManager, so adding a new
 * gateway is: (1) implement this interface, (2) register in the Manager.
 */
interface PaymentGatewayInterface
{
    /** Unique identifier — matches route param and config key. */
    public function slug(): string;

    /** How this gateway works (redirect / monitor / manual). */
    public function type(): GatewayType;

    /** Whether this gateway is configured and usable. */
    public function isEnabled(): bool;

    /**
     * For Redirect gateways: returns the URL to send the user to.
     * For Monitor gateways: returns null (user sees transfer instructions).
     * For Manual gateway: returns null.
     */
    public function buildCheckoutUrl(SubscriptionInvoice $invoice, string $returnUrl): ?string;

    /**
     * Verify the incoming webhook/IPN signature.
     * Must NOT throw — return false on invalid signature.
     */
    public function verifyWebhook(Request $request): bool;

    /**
     * Extract the invoice reference (invoice_number) from the verified
     * webhook payload so we can find the correct invoice.
     */
    public function extractInvoiceNumber(Request $request): string;

    /**
     * Extract the payment reference (gateway's own transaction ID) from
     * the verified payload.
     */
    public function extractPaymentRef(Request $request): string;

    /**
     * Extract the transferred amount (in VND) from the webhook payload.
     * For gateways where amount mismatch needs detecting (SePay).
     */
    public function extractAmount(Request $request): float;

    /**
     * The JSON body to respond to the gateway with after processing its
     * webhook. Gateways expect specific formats; not returning the right
     * structure can cause the gateway to retry indefinitely.
     */
    public function webhookAck(bool $success): array;

    /**
     * Verify the return URL hit (user coming back from gateway).
     * Only relevant for Redirect gateways. Returns null for others.
     */
    public function verifyReturn(Request $request): ?bool;
}

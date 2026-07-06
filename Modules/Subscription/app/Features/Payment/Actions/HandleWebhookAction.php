<?php

namespace Modules\Subscription\Features\Payment\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Subscription\Enums\TransactionStatus;
use Modules\Subscription\Features\Payment\Contracts\PaymentGatewayInterface;
use Modules\Subscription\Features\Payment\Support\PaymentGatewayManager;
use Modules\Subscription\Models\PaymentTransaction;
use Modules\Subscription\Models\SubscriptionInvoice;

class HandleWebhookAction
{
    use AsAction;

    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly MarkInvoicePaidAction $markPaid,
    ) {}

    /**
     * Returns the JSON ack array the controller must send back to the gateway.
     * Never throws — gateway expects a response, not an error page.
     */
    public function handle(string $gatewaySlug, Request $request): array
    {
        try {
            $gateway = $this->gateways->resolve($gatewaySlug);
        } catch (\InvalidArgumentException $e) {
            Log::warning("Webhook received for unknown gateway: {$gatewaySlug}");
            return ['success' => false, 'message' => 'Unknown gateway'];
        }

        $rawPayload = json_encode($request->all());

        // 1. Verify signature before any DB write
        if (!$gateway->verifyWebhook($request)) {
            Log::warning("Webhook signature verification failed", [
                'gateway' => $gatewaySlug,
                'ip'      => $request->ip(),
            ]);
            return $gateway->webhookAck(false);
        }

        $invoiceNumber = $gateway->extractInvoiceNumber($request);
        $paymentRef    = $gateway->extractPaymentRef($request);
        $amount        = $gateway->extractAmount($request);

        if (!$invoiceNumber) {
            Log::warning("Webhook: could not extract invoice number", ['gateway' => $gatewaySlug]);
            return $gateway->webhookAck(false);
        }

        $invoice = SubscriptionInvoice::withoutTenant()
            ->where('invoice_number', $invoiceNumber)
            ->first();

        if (!$invoice) {
            Log::warning("Webhook: invoice not found [{$invoiceNumber}]", ['gateway' => $gatewaySlug]);
            return $gateway->webhookAck(false);
        }

        // 2. Idempotency — if already paid, ack success without re-processing
        if ($invoice->isPaid()) {
            $this->logTransaction($invoice, $gateway, $paymentRef, $amount, $rawPayload, $request->ip(), TransactionStatus::Duplicate);
            return $gateway->webhookAck(true);
        }

        // 3. Amount sanity check (SePay bank transfer can mismatch)
        if ($amount > 0 && abs($amount - (float) $invoice->amount) > 1) {
            Log::warning("Webhook: amount mismatch for {$invoiceNumber}", [
                'expected' => $invoice->amount,
                'received' => $amount,
            ]);
            $this->logTransaction($invoice, $gateway, $paymentRef, $amount, $rawPayload, $request->ip(), TransactionStatus::Failed);
            return $gateway->webhookAck(false);
        }

        try {
            $this->markPaid->handle($invoice, $paymentRef, $gatewaySlug);
            $this->logTransaction($invoice, $gateway, $paymentRef, $amount, $rawPayload, $request->ip(), TransactionStatus::Confirmed);
        } catch (\Throwable $e) {
            Log::error("Webhook: MarkInvoicePaidAction failed for {$invoiceNumber}", ['error' => $e->getMessage()]);
            $this->logTransaction($invoice, $gateway, $paymentRef, $amount, $rawPayload, $request->ip(), TransactionStatus::Failed);
            return $gateway->webhookAck(false);
        }

        return $gateway->webhookAck(true);
    }

    private function logTransaction(
        SubscriptionInvoice     $invoice,
        PaymentGatewayInterface $gateway,
        string                  $paymentRef,
        float                   $amount,
        ?string                 $rawPayload,
        ?string                 $ipAddr,
        TransactionStatus       $status,
    ): void {
        try {
            PaymentTransaction::create([
                'organization_id' => $invoice->organization_id,
                'invoice_id'      => $invoice->id,
                'gateway'         => $gateway->slug(),
                'direction'       => 'inbound',
                'status'          => $status,
                'gateway_ref'     => $paymentRef,
                'amount'          => $amount,
                'raw_payload'     => $rawPayload,
                'ip_addr'         => $ipAddr,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to log payment transaction', ['error' => $e->getMessage()]);
        }
    }
}

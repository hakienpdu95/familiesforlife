<?php

namespace Modules\Subscription\Features\Payment\Http;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Subscription\Features\Payment\Actions\MarkInvoicePaidAction;
use Modules\Subscription\Features\Payment\Support\PaymentGatewayManager;
use Modules\Subscription\Models\SubscriptionInvoice;

class PaymentReturnController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly MarkInvoicePaidAction $markPaid,
    ) {}

    /** GET /billing/payment/return/{gateway} — user comes back from redirect gateway. */
    public function handleReturn(Request $request, string $gateway)
    {
        try {
            $gw      = $this->gateways->resolve($gateway);
            $success = $gw->verifyReturn($request);
        } catch (\InvalidArgumentException) {
            return redirect()->route('subscription.portal.billing')->with('error', 'Cổng thanh toán không hợp lệ.');
        }

        // Return URL is informational only — IPN is authoritative for plan activation.
        // We just show the user the correct status page.
        $invoiceNumber = $this->gateways->resolve($gateway)->extractInvoiceNumber($request);
        $invoice       = $invoiceNumber
            ? SubscriptionInvoice::withoutTenant()->where('invoice_number', $invoiceNumber)->first()
            : null;

        return view('subscription::payment.return', [
            'success' => $success ?? false,
            'invoice' => $invoice,
            'gateway' => $gateway,
        ]);
    }

    /** GET /billing/payment/transfer/{invoice} — SePay bank transfer instructions. */
    public function showTransfer(Request $request, SubscriptionInvoice $invoice)
    {
        $this->authorize('view', $invoice);

        return view('subscription::payment.transfer', [
            'invoice'        => $invoice,
            'bank_name'      => config('subscription.gateways.sepay.bank_name', 'MB Bank'),
            'account_number' => config('subscription.gateways.sepay.account_number'),
            'account_name'   => config('subscription.gateways.sepay.account_name'),
        ]);
    }

    /** GET /billing/payment/manual/{invoice} — admin/dev manual confirm page. */
    public function showManual(Request $request, SubscriptionInvoice $invoice)
    {
        return view('subscription::payment.manual', compact('invoice'));
    }

    /** POST /billing/payment/manual/{invoice}/confirm */
    public function confirmManual(Request $request, SubscriptionInvoice $invoice): RedirectResponse
    {
        if (!$invoice->isPaid()) {
            $this->markPaid->handle($invoice, 'MANUAL-' . now()->format('YmdHis'), 'manual');
        }

        return redirect()->route('subscription.portal.billing')
            ->with('success', 'Thanh toán thủ công thành công.');
    }

    /** GET /billing/payment/cancel */
    public function cancel(Request $request)
    {
        return view('subscription::payment.cancelled');
    }
}

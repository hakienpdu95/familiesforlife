<?php

namespace Modules\Subscription\Features\Payment\Http\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Subscription\Enums\InvoiceStatus;
use Modules\Subscription\Features\Payment\Actions\MarkInvoicePaidAction;
use Modules\Subscription\Features\Payment\Actions\VoidInvoiceAction;
use Modules\Subscription\Features\Payment\Queries\GetInvoiceHandler;
use Modules\Subscription\Features\Payment\Queries\GetInvoiceQuery;
use Modules\Subscription\Features\Payment\Queries\ListInvoicesHandler;
use Modules\Subscription\Features\Payment\Queries\ListInvoicesQuery;
use Modules\Subscription\Models\SubscriptionInvoice;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly ListInvoicesHandler $listHandler,
        private readonly GetInvoiceHandler   $getHandler,
    ) {}

    public function index(Request $request)
    {
        $search   = $request->input('search', '');
        $statusIn = $request->input('status');
        $orgId    = $request->input('org_id');

        $invoices = $this->listHandler->handle(new ListInvoicesQuery(
            organizationId: $orgId ? (int) $orgId : null,
            status:         $statusIn !== null ? (int) $statusIn : null,
            search:         $search ?: null,
            forAdmin:       true,
        ));

        $statuses = InvoiceStatus::cases();

        return view('subscription::admin.invoices.index', compact(
            'invoices', 'statuses', 'search', 'statusIn', 'orgId'
        ));
    }

    public function markPaid(
        Request $request,
        SubscriptionInvoice $invoice,
        MarkInvoicePaidAction $action,
    ): RedirectResponse {
        $validated = $request->validate([
            'payment_ref'    => 'nullable|string|max:191',
            'payment_method' => 'nullable|string|max:64',
        ]);

        $action->handle(
            $invoice,
            $validated['payment_ref']    ?? 'MANUAL-ADMIN-' . now()->format('YmdHis'),
            $validated['payment_method'] ?? 'manual',
        );

        return back()->with('success', "Invoice #{$invoice->invoice_number} đã được đánh dấu thanh toán.");
    }

    public function void(
        Request $request,
        SubscriptionInvoice $invoice,
        VoidInvoiceAction $action,
    ): RedirectResponse {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $action->handle($invoice, $validated['reason'] ?? '');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['invoice' => $e->getMessage()]);
        }

        return back()->with('success', "Invoice #{$invoice->invoice_number} đã bị void.");
    }
}

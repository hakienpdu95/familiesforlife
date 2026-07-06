<?php

namespace Modules\Subscription\Features\Payment\Http\Portal;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Modules\Subscription\Features\Payment\Queries\GetInvoiceHandler;
use Modules\Subscription\Features\Payment\Queries\GetInvoiceQuery;
use Modules\Subscription\Features\Payment\Queries\ListInvoicesHandler;
use Modules\Subscription\Features\Payment\Queries\ListInvoicesQuery;
use Modules\Subscription\Models\SubscriptionInvoice;

class InvoicePortalController extends Controller
{
    public function __construct(
        private readonly ListInvoicesHandler $listHandler,
        private readonly GetInvoiceHandler   $getHandler,
    ) {}

    public function index(Request $request)
    {
        $orgId = TenantContext::getOrganizationId();

        $invoices = $this->listHandler->handle(new ListInvoicesQuery(
            organizationId: $orgId,
            status:         $request->input('status') !== null ? (int) $request->input('status') : null,
            perPage:        15,
        ));

        return view('subscription::portal.invoices.index', compact('invoices'));
    }

    public function show(SubscriptionInvoice $invoice)
    {
        $orgId = TenantContext::getOrganizationId();

        abort_unless($invoice->organization_id === $orgId, 403);

        $invoice = $this->getHandler->handle(new GetInvoiceQuery($invoice->id));

        return view('subscription::portal.invoices.show', compact('invoice'));
    }
}

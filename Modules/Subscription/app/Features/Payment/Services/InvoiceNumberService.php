<?php

namespace Modules\Subscription\Features\Payment\Services;

use App\Shared\Tenancy\Models\Organization;
use Illuminate\Support\Facades\DB;
use Modules\Subscription\Models\SubscriptionInvoice;

/**
 * Generates unique sequential invoice numbers per org.
 * Format: INV-YYYY-{org_id}-{seq:04d}
 *
 * Must be called inside the DB::transaction that also creates the invoice row
 * (GenerateInvoiceAction wraps both). The lockForUpdate on the most-recent row
 * acquires an InnoDB gap lock that blocks concurrent inserts for the same org
 * until the outer transaction commits, preventing duplicate sequence numbers.
 *
 * The unique constraint on invoice_number is the final safety net if this lock
 * is somehow bypassed (e.g. SQLite in tests, which ignores FOR UPDATE).
 */
final class InvoiceNumberService
{
    public function generate(Organization $org): string
    {
        $year = now()->year;

        // Lock the last existing row for this org+year.
        // InnoDB will hold a next-key lock that prevents phantom inserts
        // until the outer transaction (in GenerateInvoiceAction) commits.
        $last = SubscriptionInvoice::withoutTenant()
            ->where('organization_id', $org->id)
            ->whereYear('created_at', $year)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first(['invoice_number']);

        if ($last) {
            // Parse the sequence from the last invoice number: INV-YYYY-ORG-NNNN
            $parts = explode('-', $last->invoice_number);
            $seq   = str_pad((int) end($parts) + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $seq = '0001';
        }

        return "INV-{$year}-{$org->id}-{$seq}";
    }
}

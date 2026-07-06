@extends('layouts.backend')
@section('title', 'Lịch sử hóa đơn')

@section('content')
<div class="max-w-4xl mx-auto p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Lịch sử hóa đơn</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Tất cả hóa đơn subscription của tổ chức</p>
        </div>
        <a href="{{ route('subscription.portal.billing') }}" class="btn btn-ghost btn-sm gap-1.5">
            ← Billing
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex gap-2 mb-4">
        <select name="status" class="select select-bordered select-sm">
            <option value="">— Tất cả —</option>
            @foreach(\Modules\Subscription\Enums\InvoiceStatus::cases() as $s)
            <option value="{{ $s->value }}" @selected(request('status') == $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-neutral">Lọc</button>
    </form>

    {{-- Table --}}
    <div class="card bg-base-100 shadow border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                    <tr>
                        <th>Invoice #</th>
                        <th>Plan</th>
                        <th>Kỳ thanh toán</th>
                        <th class="text-right">Số tiền</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($invoices as $invoice)
                <tr class="hover">
                    <td class="font-mono text-xs font-semibold">{{ $invoice->invoice_number }}</td>
                    <td class="text-sm">{{ $invoice->plan?->name ?? '—' }}</td>
                    <td class="text-sm text-base-content/60">
                        @if($invoice->billing_period_start && $invoice->billing_period_end)
                            {{ $invoice->billing_period_start->format('d/m/Y') }} –
                            {{ $invoice->billing_period_end->format('d/m/Y') }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-right font-mono text-sm font-semibold">
                        {{ number_format($invoice->amount) }} {{ $invoice->currency }}
                    </td>
                    <td>
                        @php
                            $badge = match($invoice->status) {
                                \Modules\Subscription\Enums\InvoiceStatus::Paid    => 'badge-success',
                                \Modules\Subscription\Enums\InvoiceStatus::Pending => $invoice->isOverdue() ? 'badge-error' : 'badge-warning',
                                \Modules\Subscription\Enums\InvoiceStatus::Void    => 'badge-ghost',
                                default => 'badge-outline',
                            };
                        @endphp
                        <span class="badge {{ $badge }} badge-sm">{{ $invoice->status->label() }}</span>
                    </td>
                    <td class="text-sm text-base-content/60">{{ $invoice->created_at->format('d/m/Y') }}</td>
                    <td>
                        <a href="{{ route('subscription.portal.invoices.show', $invoice) }}"
                           class="btn btn-xs btn-ghost">Xem</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-10 text-base-content/40">
                        Chưa có hóa đơn nào.
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
        <div class="px-4 py-3 border-t border-base-200">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

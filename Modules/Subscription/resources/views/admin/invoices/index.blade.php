@extends('layouts.backend')
@section('title', 'Quản lý Invoices')

@section('content')
<div x-data="{
    modal: null,
    invoiceNumber: '',
    openModal(type, id, number) {
        this.modal = type + '-' + id;
        this.invoiceNumber = number;
    }
}">

    {{-- Flash --}}
    @foreach(['success','error'] as $type)
        @if(session($type))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition.opacity class="alert alert-{{ $type }} mb-4 text-sm">
            <span>{{ session($type) }}</span>
            <button @click="show = false" class="btn btn-ghost btn-xs ml-auto">✕</button>
        </div>
        @endif
    @endforeach
    @if($errors->any())
    <div class="alert alert-error mb-4 text-sm">
        <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold">Invoices</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Quản lý hóa đơn thanh toán subscription</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-2 mb-5">
        <input type="text" name="search" value="{{ $search }}" placeholder="Số invoice..."
               class="input input-bordered input-sm w-44">
        <select name="status" class="select select-bordered select-sm">
            <option value="">— Tất cả trạng thái —</option>
            @foreach($statuses as $s)
            <option value="{{ $s->value }}" @selected((string)$statusIn === (string)$s->value)>
                {{ $s->label() }}
            </option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-neutral">Lọc</button>
        @if($search || $statusIn !== null || $orgId)
        <a href="{{ route('subscription.admin.invoices.index') }}" class="btn btn-sm btn-ghost">Xoá lọc</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                    <tr>
                        <th>Invoice #</th>
                        <th>Tổ chức</th>
                        <th>Plan</th>
                        <th class="text-right">Số tiền</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th>Đáo hạn</th>
                        <th>Thanh toán</th>
                        <th class="w-28"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($invoices as $invoice)
                <tr class="hover">
                    <td class="font-mono text-xs font-semibold">{{ $invoice->invoice_number }}</td>
                    <td>
                        <span class="font-medium text-sm">{{ $invoice->organization?->name ?? '—' }}</span>
                    </td>
                    <td class="text-sm">{{ $invoice->plan?->name ?? '—' }}</td>
                    <td class="text-right font-mono text-sm">
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
                        <span class="badge {{ $badge }} badge-sm gap-1">
                            {{ $invoice->status->label() }}
                            @if($invoice->isOverdue()) (Quá hạn) @endif
                        </span>
                    </td>
                    <td class="text-sm text-base-content/60">{{ $invoice->created_at->format('d/m/Y') }}</td>
                    <td class="text-sm {{ $invoice->isOverdue() ? 'text-error font-semibold' : 'text-base-content/60' }}">
                        {{ $invoice->due_date?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="text-sm text-base-content/60">
                        {{ $invoice->paid_at?->format('d/m/Y H:i') ?? '—' }}
                    </td>
                    <td>
                        <div class="flex gap-1">
                            @if($invoice->status === \Modules\Subscription\Enums\InvoiceStatus::Pending)
                            <button @click="openModal('paid', {{ $invoice->id }}, '{{ $invoice->invoice_number }}')"
                                    class="btn btn-xs btn-success">Mark Paid</button>
                            <button @click="openModal('void', {{ $invoice->id }}, '{{ $invoice->invoice_number }}')"
                                    class="btn btn-xs btn-ghost text-error">Void</button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-8 text-base-content/40">Không có invoice nào.</td>
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

    {{-- Modal: Mark Paid --}}
    @foreach($invoices as $invoice)
    @if($invoice->status === \Modules\Subscription\Enums\InvoiceStatus::Pending)
    <div x-show="modal === 'paid-{{ $invoice->id }}'" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
         @click.self="modal = null">
        <div class="bg-base-100 rounded-xl shadow-xl w-full max-w-sm p-6" @click.stop>
            <h3 class="text-lg font-bold mb-4">Xác nhận thanh toán</h3>
            <p class="text-sm text-base-content/70 mb-4">
                Invoice <strong x-text="invoiceNumber"></strong>
            </p>
            <form method="POST" action="{{ route('subscription.admin.invoices.mark-paid', $invoice) }}">
                @csrf
                <div class="form-control mb-3">
                    <label class="label label-text text-xs">Mã thanh toán (payment ref)</label>
                    <input type="text" name="payment_ref" class="input input-bordered input-sm"
                           placeholder="VD: TXN-2026-...">
                </div>
                <div class="form-control mb-4">
                    <label class="label label-text text-xs">Phương thức</label>
                    <select name="payment_method" class="select select-bordered select-sm">
                        <option value="manual">Thủ công</option>
                        <option value="bank_transfer">Chuyển khoản</option>
                        <option value="cash">Tiền mặt</option>
                        <option value="vnpay">VNPay</option>
                    </select>
                </div>
                <div class="flex gap-2 justify-end">
                    <button type="button" @click="modal = null" class="btn btn-ghost btn-sm">Hủy</button>
                    <button type="submit" class="btn btn-success btn-sm">Xác nhận</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Void --}}
    <div x-show="modal === 'void-{{ $invoice->id }}'" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
         @click.self="modal = null">
        <div class="bg-base-100 rounded-xl shadow-xl w-full max-w-sm p-6" @click.stop>
            <h3 class="text-lg font-bold mb-4">Void invoice</h3>
            <p class="text-sm text-base-content/70 mb-4">
                Invoice <strong x-text="invoiceNumber"></strong> sẽ bị hủy bỏ.
            </p>
            <form method="POST" action="{{ route('subscription.admin.invoices.void', $invoice) }}">
                @csrf
                <div class="form-control mb-4">
                    <label class="label label-text text-xs">Lý do (tuỳ chọn)</label>
                    <input type="text" name="reason" class="input input-bordered input-sm"
                           placeholder="Lý do void...">
                </div>
                <div class="flex gap-2 justify-end">
                    <button type="button" @click="modal = null" class="btn btn-ghost btn-sm">Hủy</button>
                    <button type="submit" class="btn btn-error btn-sm">Void</button>
                </div>
            </form>
        </div>
    </div>
    @endif
    @endforeach

</div>
@endsection

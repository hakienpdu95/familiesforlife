@extends('layouts.backend')
@section('title', 'Hóa đơn ' . $invoice->invoice_number)

@section('content')
<div class="max-w-2xl mx-auto p-6">

    {{-- Back --}}
    <div class="mb-5">
        <a href="{{ route('subscription.portal.invoices') }}" class="btn btn-ghost btn-sm gap-1.5">
            ← Danh sách hóa đơn
        </a>
    </div>

    <div class="card bg-base-100 shadow border border-base-200">
        <div class="card-body gap-6">

            {{-- Header --}}
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold font-mono">{{ $invoice->invoice_number }}</h1>
                    <p class="text-sm text-base-content/50 mt-0.5">
                        Ngày tạo: {{ $invoice->created_at->format('d/m/Y H:i') }}
                    </p>
                </div>
                @php
                    $badge = match($invoice->status) {
                        \Modules\Subscription\Enums\InvoiceStatus::Paid    => 'badge-success',
                        \Modules\Subscription\Enums\InvoiceStatus::Pending => $invoice->isOverdue() ? 'badge-error' : 'badge-warning',
                        \Modules\Subscription\Enums\InvoiceStatus::Void    => 'badge-ghost',
                        default => 'badge-outline',
                    };
                @endphp
                <span class="badge {{ $badge }} badge-lg">{{ $invoice->status->label() }}</span>
            </div>

            <div class="divider my-0"></div>

            {{-- Invoice details --}}
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div>
                    <dt class="text-base-content/50 text-xs uppercase tracking-wide">Plan</dt>
                    <dd class="font-semibold mt-0.5">{{ $invoice->plan?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-base-content/50 text-xs uppercase tracking-wide">Số tiền</dt>
                    <dd class="font-bold text-lg mt-0.5">
                        {{ number_format($invoice->amount) }} {{ $invoice->currency }}
                    </dd>
                </div>
                @if($invoice->billing_period_start)
                <div>
                    <dt class="text-base-content/50 text-xs uppercase tracking-wide">Kỳ bắt đầu</dt>
                    <dd class="mt-0.5">{{ $invoice->billing_period_start->format('d/m/Y') }}</dd>
                </div>
                @endif
                @if($invoice->billing_period_end)
                <div>
                    <dt class="text-base-content/50 text-xs uppercase tracking-wide">Kỳ kết thúc</dt>
                    <dd class="mt-0.5">{{ $invoice->billing_period_end->format('d/m/Y') }}</dd>
                </div>
                @endif
                @if($invoice->due_date)
                <div>
                    <dt class="text-base-content/50 text-xs uppercase tracking-wide">Ngày đáo hạn</dt>
                    <dd class="mt-0.5 {{ $invoice->isOverdue() ? 'text-error font-semibold' : '' }}">
                        {{ $invoice->due_date->format('d/m/Y') }}
                        @if($invoice->isOverdue()) <span class="badge badge-error badge-xs ml-1">Quá hạn</span> @endif
                    </dd>
                </div>
                @endif
                @if($invoice->paid_at)
                <div>
                    <dt class="text-base-content/50 text-xs uppercase tracking-wide">Ngày thanh toán</dt>
                    <dd class="mt-0.5 text-success font-semibold">{{ $invoice->paid_at->format('d/m/Y H:i') }}</dd>
                </div>
                @endif
                @if($invoice->payment_method)
                <div>
                    <dt class="text-base-content/50 text-xs uppercase tracking-wide">Phương thức</dt>
                    <dd class="mt-0.5 capitalize">{{ $invoice->payment_method }}</dd>
                </div>
                @endif
                @if($invoice->payment_ref)
                <div class="col-span-2">
                    <dt class="text-base-content/50 text-xs uppercase tracking-wide">Mã giao dịch</dt>
                    <dd class="mt-0.5 font-mono text-xs bg-base-200 px-2 py-1 rounded">{{ $invoice->payment_ref }}</dd>
                </div>
                @endif
                @if($invoice->notes)
                <div class="col-span-2">
                    <dt class="text-base-content/50 text-xs uppercase tracking-wide">Ghi chú</dt>
                    <dd class="mt-0.5 text-base-content/70">{{ $invoice->notes }}</dd>
                </div>
                @endif
            </dl>

            {{-- Payment action for pending invoices --}}
            @if($invoice->status === \Modules\Subscription\Enums\InvoiceStatus::Pending)
            <div class="divider my-0"></div>
            <div class="flex flex-col gap-2">
                <p class="text-sm text-warning font-medium">
                    Hóa đơn này chưa được thanh toán.
                    @if($invoice->due_date) Vui lòng thanh toán trước {{ $invoice->due_date->format('d/m/Y') }}. @endif
                </p>
                <a href="{{ route('subscription.portal.billing') }}" class="btn btn-primary btn-sm w-fit">
                    Đến trang thanh toán
                </a>
            </div>
            @endif

        </div>
    </div>

</div>
@endsection

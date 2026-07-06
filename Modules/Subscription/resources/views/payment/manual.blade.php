@extends('layouts.backend')

@section('title', 'Xác nhận thanh toán thủ công')

@section('content')
<div class="p-6 max-w-md mx-auto">
    <div class="card bg-base-100 shadow">
        <div class="card-body space-y-4">
            <h2 class="card-title">Thanh toán thủ công (Dev/Admin)</h2>
            <div class="alert alert-warning text-sm">
                Chức năng này chỉ dành cho môi trường dev hoặc admin. Không dùng trong production.
            </div>

            <div class="space-y-2 text-sm">
                <p>Hóa đơn: <strong>{{ $invoice->invoice_number }}</strong></p>
                <p>Số tiền: <strong>{{ number_format($invoice->amount, 0, ',', '.') }} {{ $invoice->currency }}</strong></p>
                <p>Trạng thái: <strong>{{ $invoice->status->label() }}</strong></p>
            </div>

            @if(!$invoice->isPaid())
            <form method="POST" action="{{ route('subscription.billing.manual.confirm', $invoice) }}">
                @csrf
                <button class="btn btn-success w-full">Xác nhận đã thanh toán</button>
            </form>
            @else
            <div class="alert alert-success text-sm">Hóa đơn này đã được thanh toán.</div>
            @endif

            <a href="{{ route('subscription.portal.billing') }}" class="btn btn-ghost btn-sm">Quay lại</a>
        </div>
    </div>
</div>
@endsection

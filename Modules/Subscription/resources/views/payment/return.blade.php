@extends('layouts.backend')

@section('title', $success ? 'Thanh toán thành công' : 'Thanh toán thất bại')

@section('content')
<div class="p-6 max-w-md mx-auto">
    <div class="card bg-base-100 shadow text-center">
        <div class="card-body space-y-4 py-10">

            @if($success)
            <div class="w-16 h-16 bg-success/10 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-8 h-8 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold">Thanh toán thành công!</h2>
            <p class="text-base-content/60">
                Giao dịch đã được ghi nhận. Subscription của bạn sẽ được kích hoạt trong vài giây.
            </p>
            @if($invoice)
            <p class="text-sm text-base-content/50">Mã hóa đơn: {{ $invoice->invoice_number }}</p>
            @endif

            @else
            <div class="w-16 h-16 bg-error/10 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-8 h-8 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold">Thanh toán thất bại</h2>
            <p class="text-base-content/60">
                Giao dịch không thành công hoặc đã bị hủy. Vui lòng thử lại.
            </p>
            @endif

            <div class="flex gap-2 justify-center mt-4">
                <a href="{{ route('subscription.portal.billing') }}" class="btn btn-ghost btn-sm">Quay lại Billing</a>
                @if(!$success)
                <a href="{{ route('subscription.portal.plans') }}" class="btn btn-primary btn-sm">Thử lại</a>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection

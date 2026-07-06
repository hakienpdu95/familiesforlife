@extends('layouts.backend')

@section('title', 'Đã hủy thanh toán')

@section('content')
<div class="p-6 max-w-md mx-auto">
    <div class="card bg-base-100 shadow text-center">
        <div class="card-body py-10 space-y-4">
            <div class="w-16 h-16 bg-base-200 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-8 h-8 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold">Thanh toán đã bị hủy</h2>
            <p class="text-base-content/60">Bạn đã hủy giao dịch. Subscription chưa thay đổi.</p>
            <div class="flex gap-2 justify-center mt-2">
                <a href="{{ route('subscription.portal.billing') }}" class="btn btn-ghost btn-sm">Billing</a>
                <a href="{{ route('subscription.portal.plans') }}" class="btn btn-primary btn-sm">Xem gói</a>
            </div>
        </div>
    </div>
</div>
@endsection

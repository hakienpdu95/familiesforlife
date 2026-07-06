@extends('layouts.backend')

@section('title', 'Thanh toán')

@section('content')
<div class="p-6 max-w-lg mx-auto space-y-6">

    @if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    <div class="card bg-base-100 shadow">
        <div class="card-body space-y-4">
            <h2 class="card-title">Xác nhận thanh toán</h2>

            <div class="bg-base-200 rounded-lg p-4 space-y-1">
                <p class="font-semibold text-lg">{{ $plan->name }}</p>
                <p class="text-2xl font-bold">
                    {{ number_format($plan->price, 0, ',', '.') }}
                    <span class="text-base font-normal text-base-content/60">{{ $plan->currency }}/tháng</span>
                </p>
            </div>

            <form method="POST" action="{{ route('subscription.billing.checkout.initiate') }}">
                @csrf
                <input type="hidden" name="plan_id" value="{{ $plan->id }}">

                <div class="space-y-3">
                    <p class="font-medium">Chọn phương thức thanh toán:</p>

                    @foreach($gateways as $gw)
                    <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-base-200
                                  has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                        <input type="radio" name="gateway" value="{{ $gw->slug() }}" class="radio radio-primary"
                               {{ $loop->first ? 'checked' : '' }}>
                        <span>
                            @switch($gw->slug())
                                @case('vnpay')
                                    <span class="font-medium">VNPay</span>
                                    <span class="text-sm text-base-content/60 ml-2">Thanh toán qua cổng VNPay</span>
                                    @break
                                @case('sepay')
                                    <span class="font-medium">Chuyển khoản ngân hàng</span>
                                    <span class="text-sm text-base-content/60 ml-2">Qua SePay — tự động xác nhận</span>
                                    @break
                                @case('manual')
                                    <span class="font-medium">Xác nhận thủ công</span>
                                    <span class="text-sm text-base-content/60 ml-2">Admin xác nhận</span>
                                    @break
                                @default
                                    <span class="font-medium">{{ ucfirst($gw->slug()) }}</span>
                            @endswitch
                        </span>
                    </label>
                    @endforeach
                </div>

                <div class="card-actions justify-end mt-6">
                    <a href="{{ route('subscription.portal.plans') }}" class="btn btn-ghost">Quay lại</a>
                    <button type="submit" class="btn btn-primary">Tiếp tục thanh toán</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
